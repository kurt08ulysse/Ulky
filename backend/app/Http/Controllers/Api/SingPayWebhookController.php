<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\ReceiptGeneratorService;
use App\Services\SingPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SingPayWebhookController extends Controller
{
    public function __construct(
        protected ReceiptGeneratorService $receiptGenerator,
        protected SingPayService $singPay,
        protected AuditService $audit,
    ) {}

    /**
     * Gère les notifications de paiement SingPay.
     *
     * Pré-requis : signature HMAC déjà vérifiée par le middleware singpay.signed.
     * Cette méthode ajoute la confirmation serveur→serveur via SingPay::checkTransactionStatus
     * avant tout changement d'état, et journalise chaque étape dans l'audit log.
     */
    public function handle(Request $request): JsonResponse
    {
        $this->audit->record('webhook.received', null, [
            'reference' => $request->input('reference'),
            'status' => $request->input('status'),
            'transaction_id' => $request->input('transaction_id') ?? $request->input('id'),
        ]);

        $reference = $request->input('reference');
        $claimedStatus = strtolower((string) $request->input('status', ''));
        $transactionId = $request->input('transaction_id') ?? $request->input('id');

        if (! $reference) {
            return response()->json([
                'success' => false,
                'message' => 'Référence de transaction manquante.',
            ], 400);
        }

        if (! preg_match('/^PAY-(\d+)-/', $reference, $matches)) {
            return response()->json([
                'success' => false,
                'message' => 'Format de référence invalide.',
            ], 400);
        }

        $payment = Payment::find((int) $matches[1]);

        if (! $payment) {
            $this->audit->record('webhook.unknown_payment', null, ['reference' => $reference]);

            return response()->json([
                'success' => false,
                'message' => 'Paiement introuvable.',
            ], 404);
        }

        // Idempotence : payment déjà confirmé → noop, mais on a tracé la réception ci-dessus.
        if ($payment->status === 'successful') {
            return response()->json([
                'success' => true,
                'message' => 'Paiement déjà traité.',
            ]);
        }

        // Confirmation serveur→serveur. Le webhook seul ne fait pas autorité.
        if (in_array($claimedStatus, ['successful', 'success', 'completed'], true) && $transactionId) {
            $check = $this->singPay->checkTransactionStatus((string) $transactionId);
            $confirmedStatus = strtolower((string) ($check['status'] ?? ''));

            if (! in_array($confirmedStatus, ['successful', 'success', 'completed'], true)) {
                $this->audit->record('webhook.status_mismatch', $payment, [
                    'webhook_claimed' => $claimedStatus,
                    'singpay_confirmed' => $confirmedStatus,
                    'transaction_id' => $transactionId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Statut webhook divergent de la confirmation SingPay.',
                ], 409);
            }

            $payment->status = 'successful';
            $payment->transaction_id = $transactionId;
            $payment->raw_response = $request->all();
            $payment->save();

            // L'objet réglé (taxe, loyer ou démarche) applique son propre effet de
            // confirmation. Pour une démarche, cela ne touche que payment_status.
            $payable = $payment->payable;
            $payable->markAsPaid();
            $payable->save();

            $this->audit->record('payment.confirmed', $payment, [
                'transaction_id' => $transactionId,
                'payable_type' => $payment->payable_type,
                'payable_id' => $payment->payable_id,
            ]);

            try {
                $receipt = $this->receiptGenerator->generate($payment);
                $this->audit->record('receipt.generated', $receipt, [
                    'receipt_number' => $receipt->receipt_number,
                ]);
            } catch (\Exception $e) {
                Log::error('Receipt generation failed: '.$e->getMessage(), ['payment_id' => $payment->id]);
                // Pas de 500 vers l'agrégateur — on ne veut pas qu'il rejoue le paiement.
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement traité et quittance générée.',
            ]);
        }

        if (in_array($claimedStatus, ['failed', 'fail', 'cancelled'], true)) {
            $payment->status = 'failed';
            $payment->raw_response = $request->all();
            $payment->save();

            $this->audit->record('payment.failed', $payment, [
                'status' => $claimedStatus,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Échec de paiement enregistré.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification reçue (statut en attente).',
        ]);
    }
}
