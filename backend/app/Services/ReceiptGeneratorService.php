<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Receipt;
use App\Models\ReceiptCounter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReceiptGeneratorService
{
    /**
     * Génère une quittance séquentielle et son PDF associé pour un paiement réussi.
     */
    public function generate(Payment $payment): Receipt
    {
        $taxNotice = $payment->taxNotice;
        $communeId = $taxNotice->commune_id;
        $year = now()->year;

        // Étape 1 : Génération du numéro de quittance de manière strictement séquentielle
        $receipt = DB::transaction(function () use ($payment, $communeId, $year) {
            // Verrouille la ligne du compteur pour la commune et l'année en cours
            $counter = ReceiptCounter::where('commune_id', $communeId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = ReceiptCounter::create([
                    'commune_id' => $communeId,
                    'year' => $year,
                    'last_number' => 0,
                ]);
            }

            $counter->last_number += 1;
            $counter->save();

            // Formatage : QTY-COMMUNE-ANNEE-NUMERO (ex: QTY-1-2026-00001)
            $communeCode = $communeId ? $communeId : 'SYS';
            $receiptNumber = sprintf(
                'QTY-%s-%d-%s',
                $communeCode,
                $year,
                str_pad($counter->last_number, 5, '0', STR_PAD_LEFT)
            );

            $qrCodeToken = Str::random(40);

            return Receipt::create([
                'payment_id' => $payment->id,
                'receipt_number' => $receiptNumber,
                'qr_code_token' => $qrCodeToken,
            ]);
        });

        // Étape 2 : Génération du PDF
        $receipt->pdf_path = $this->generatePdf($receipt);
        $receipt->save();

        return $receipt;
    }

    /**
     * Génère le PDF de la quittance et le stocke sur le disque.
     */
    protected function generatePdf(Receipt $receipt): string
    {
        $payment = $receipt->payment;
        $taxNotice = $payment->taxNotice;
        $user = $taxNotice->user;
        $tax = $taxNotice->tax;

        // Préparation des données pour la vue
        $data = [
            'receipt' => $receipt,
            'payment' => $payment,
            'taxNotice' => $taxNotice,
            'user' => $user,
            'tax' => $tax,
            'verification_url' => $receipt->verification_url,
            // Utilisation d'une API publique gratuite pour les QR codes
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.urlencode($receipt->verification_url),
        ];

        // Rendu du PDF
        $pdf = Pdf::loadView('receipts.pdf', $data);

        $fileName = "receipts/{$receipt->receipt_number}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        return $fileName;
    }
}
