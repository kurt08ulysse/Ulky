<?php

// QR code généré localement via bacon/bacon-qr-code ^3.1 (backend GD).
// Aucun appel HTTP sortant : le token de vérification ne fuit jamais vers un tiers.
// Alternative écartée : simplesoftwareio/simple-qrcode (wrapper Laravel autour de bacon,
// sans valeur ajoutée et avec un cycle de release plus lent).

namespace App\Services;

use App\Models\Payment;
use App\Models\Receipt;
use App\Models\ReceiptCounter;
use App\Models\StallRent;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReceiptGeneratorService
{
    /**
     * Génère une quittance séquentielle (sans trou) et son PDF associé.
     */
    public function generate(Payment $payment): Receipt
    {
        // payable = objet réglé (TaxNotice aujourd'hui ; StallRent en Phase 5 marchés).
        $taxNotice = $payment->payable;
        $communeId = $taxNotice->commune_id;
        $year = now()->year;

        $receipt = DB::transaction(function () use ($payment, $communeId, $year) {
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

            $communeCode = $communeId ?: 'SYS';
            $receiptNumber = sprintf(
                'QTY-%s-%d-%s',
                $communeCode,
                $year,
                str_pad((string) $counter->last_number, 5, '0', STR_PAD_LEFT)
            );

            return Receipt::create([
                'payment_id' => $payment->id,
                'receipt_number' => $receiptNumber,
                'qr_code_token' => Str::random(40),
            ]);
        });

        $receipt->pdf_path = $this->generatePdf($receipt);
        $receipt->save();

        return $receipt;
    }

    protected function generatePdf(Receipt $receipt): string
    {
        $payment = $receipt->payment;
        $payable = $payment->payable;

        $common = [
            'receipt' => $receipt,
            'payment' => $payment,
            'verification_url' => $receipt->verification_url,
            'qr_code_url' => $this->buildQrDataUri($receipt->verification_url),
        ];

        // Le template diffère selon l'objet réglé : taxe vs loyer de marché.
        if ($payable instanceof StallRent) {
            $view = 'receipts.rent_pdf';
            $data = $common + [
                'rent' => $payable,
                'occupant' => $payable->occupant,
                'stall' => $payable->stall,
                'market' => $payable->stall?->market,
            ];
        } else {
            $view = 'receipts.pdf';
            $data = $common + [
                'taxNotice' => $payable,
                'user' => $payable->user,
                'tax' => $payable->tax,
            ];
        }

        $pdf = Pdf::loadView($view, $data);

        $fileName = "receipts/{$receipt->receipt_number}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        return $fileName;
    }

    /**
     * Renvoie un QR code PNG encodé en data URI base64, prêt pour <img src="...">.
     */
    protected function buildQrDataUri(string $payload): string
    {
        $renderer = new GDLibRenderer(300, 1);
        $writer = new Writer($renderer);
        $png = $writer->writeString($payload);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
