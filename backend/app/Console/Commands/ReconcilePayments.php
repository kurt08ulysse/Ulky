<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\AuditService;
use App\Services\SingPayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Réconciliation quotidienne (Phase 3, régie de recettes).
 *
 * Rapproche chaque paiement local de son statut réel chez SingPay
 * (confirmation serveur→serveur). Tout écart est :
 *   - tracé dans le journal d'audit (reconciliation.discrepancy, append-only) ;
 *   - bloquant : la commande sort en code != 0 pour déclencher une alerte
 *     (échec planifié visible en supervision).
 *
 * Non destructif : ne modifie aucun paiement (lecture + audit). Ré-exécutable.
 */
class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile {--date= : Jour à réconcilier (YYYY-MM-DD), défaut aujourd\'hui}';

    protected $description = 'Réconcilie les paiements locaux avec SingPay et trace tout écart.';

    public function handle(SingPayService $singPay, AuditService $audit): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();

        $payments = Payment::whereNotNull('transaction_id')
            ->whereDate('created_at', $date)
            ->get();

        $discrepancies = 0;

        foreach ($payments as $payment) {
            $remote = $singPay->checkTransactionStatus((string) $payment->transaction_id);
            $remoteStatus = strtolower((string) ($remote['status'] ?? ''));
            $remoteSuccessful = in_array($remoteStatus, ['successful', 'success', 'completed'], true);
            $localSuccessful = $payment->status === 'successful';

            if ($localSuccessful !== $remoteSuccessful) {
                $discrepancies++;
                $audit->record('reconciliation.discrepancy', $payment, [
                    'local_status' => $payment->status,
                    'singpay_status' => $remoteStatus,
                    'transaction_id' => $payment->transaction_id,
                    'date' => $date->toDateString(),
                ]);
                $this->error("Écart paiement #{$payment->id} : local={$payment->status} / SingPay={$remoteStatus}");
            }
        }

        $audit->record('reconciliation.run', null, [
            'date' => $date->toDateString(),
            'checked' => $payments->count(),
            'discrepancies' => $discrepancies,
        ]);

        $this->info("Réconciliation {$date->toDateString()} : {$payments->count()} paiement(s) vérifié(s), {$discrepancies} écart(s).");

        // Tout écart est bloquant.
        return $discrepancies === 0 ? self::SUCCESS : self::FAILURE;
    }
}
