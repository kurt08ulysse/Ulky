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
        $taxNotice = $receipt->payment->payable;

        // Restriction de sécurité (deny-by-default) : seuls le propriétaire de l'avis
        // ou un agent/admin municipal peuvent télécharger la quittance. Tout autre
        // cas (y compris un utilisateur sans rôle) est refusé.
        $isOwner = $taxNotice->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['municipal_agent', 'cashier', 'commune_admin', 'super_admin']);

        if (! $isOwner && ! $isStaff) {
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
            ->with(['payment.payable'])
            ->first();

        if (! $receipt) {
            abort(404, "Quittance invalide ou introuvable. Ce document n'est pas authentique.");
        }

        $payable = $receipt->payment->payable;

        return view('receipts.verify', [
            'receipt' => $receipt,
            'payment' => $receipt->payment,
            'taxNotice' => $payable,
            'user' => $payable->user,
            'tax' => $payable->tax,
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
