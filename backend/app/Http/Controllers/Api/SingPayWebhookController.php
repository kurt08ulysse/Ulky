<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\ReceiptGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SingPayWebhookController extends Controller
{
    protected ReceiptGeneratorService $receiptGenerator;

    public function __construct(ReceiptGeneratorService $receiptGenerator)
    {
        $this->receiptGenerator = $receiptGenerator;
    }

    /**
     * Gère les notifications de paiement envoyées par SingPay (Webhooks).
     */
    public function handle(Request $request): JsonResponse
    {
        Log::info('SingPay Webhook received', $request->all());

        $reference = $request->input('reference');
        $status = strtolower($request->input('status', ''));
        $transactionId = $request->input('transaction_id') ?? $request->input('id');

        if (! $reference) {
            return response()->json([
                'success' => false,
                'message' => 'Référence de transaction manquante.',
            ], 400);
        }

        // Extrait l'ID du paiement de la référence (format : PAY-{payment_id}-{timestamp})
        if (! preg_match('/^PAY-(\d+)-/', $reference, $matches)) {
            return response()->json([
                'success' => false,
                'message' => 'Format de référence invalide.',
            ], 400);
        }

        $paymentId = (int) $matches[1];
        $payment = Payment::find($paymentId);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => "Paiement local #{$paymentId} introuvable.",
            ], 404);
        }

        // Idempotence : si le paiement est déjà réussi, on ne fait rien
        if ($payment->status === 'successful') {
            return response()->json([
                'success' => true,
                'message' => 'Paiement déjà traité avec succès.',
            ]);
        }

        $payment->raw_response = $request->all();
        if ($transactionId) {
            $payment->transaction_id = $transactionId;
        }

        if ($status === 'successful' || $status === 'success' || $status === 'completed') {
            // Mettre à jour le paiement local
            $payment->status = 'successful';
            $payment->save();

            // Mettre à jour l'avis de taxe
            $taxNotice = $payment->taxNotice;
            $taxNotice->status = 'paid';
            $taxNotice->paid_at = now();
            $taxNotice->save();

            Log::info("Payment #{$paymentId} marked as successful. Generating receipt...");

            // Générer la quittance officielle
            try {
                $receipt = $this->receiptGenerator->generate($payment);
                Log::info('Receipt generated successfully', [
                    'receipt_id' => $receipt->id,
                    'receipt_number' => $receipt->receipt_number,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate receipt: '.$e->getMessage(), [
                    'exception' => $e,
                ]);
                // On ne renvoie pas d'erreur 500 à l'agrégateur si la quittance échoue pour qu'il ne rejoue pas le paiement
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement traité et quittance générée avec succès.',
            ]);
        }

        // Si le statut renvoyé est un échec
        if ($status === 'failed' || $status === 'fail' || $status === 'cancelled') {
            $payment->status = 'failed';
            $payment->save();

            return response()->json([
                'success' => true,
                'message' => 'Échec du paiement enregistré.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification reçue (statut en attente ou inconnu).',
        ]);
    }
}
