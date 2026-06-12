<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    /**
     * Permet à un citoyen de télécharger son reçu de quittance PDF de manière sécurisée.
     */
    public function download(Receipt $receipt): StreamedResponse
    {
        $user = auth()->user();
        $taxNotice = $receipt->payment->taxNotice;

        // Restriction de sécurité : seuls le propriétaire ou les agents municipaux/admins peuvent télécharger
        if ($user->hasRole('citizen') && $taxNotice->user_id !== $user->id) {
            abort(403, "Vous n'êtes pas autorisé à télécharger cette quittance.");
        }

        if (! $receipt->pdf_path || ! Storage::disk('public')->exists($receipt->pdf_path)) {
            abort(404, 'Fichier PDF de quittance introuvable.');
        }

        return Storage::disk('public')->download(
            $receipt->pdf_path,
            "quittance-{$receipt->receipt_number}.pdf"
        );
    }

    /**
     * Route de vérification publique pour les QR codes de contrôle (sans authentification).
     */
    public function verify(string $token): View
    {
        $receipt = Receipt::where('qr_code_token', $token)
            ->with(['payment.taxNotice.user', 'payment.taxNotice.tax'])
            ->first();

        if (! $receipt) {
            abort(404, "Quittance invalide ou introuvable. Ce document n'est pas authentique.");
        }

        return view('receipts.verify', [
            'receipt' => $receipt,
            'payment' => $receipt->payment,
            'taxNotice' => $receipt->payment->taxNotice,
            'user' => $receipt->payment->taxNotice->user,
            'tax' => $receipt->payment->taxNotice->tax,
        ]);
    }

    /**
     * Route publique de téléchargement du PDF de la quittance (sans authentification).
     *
     * @return BinaryFileResponse|StreamedResponse
     */
    public function verifyPdf(string $token)
    {
        $receipt = Receipt::where('qr_code_token', $token)->first();

        if (! $receipt || ! $receipt->pdf_path || ! Storage::disk('public')->exists($receipt->pdf_path)) {
            abort(404, 'Quittance PDF introuvable.');
        }

        return Storage::disk('public')->download(
            $receipt->pdf_path,
            "quittance-{$receipt->receipt_number}.pdf"
        );
    }
}
