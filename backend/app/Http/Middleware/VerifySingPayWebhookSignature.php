<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie la signature HMAC-SHA256 du webhook SingPay.
 *
 * Convention adoptée en l'absence de doc SingPay vendorisée dans le repo :
 *   - Header : X-SingPay-Signature
 *   - Valeur : hex(hash_hmac('sha256', body_brut, SINGPAY_WEBHOOK_SECRET))
 *   - Comparaison via hash_equals (timing-safe).
 *
 * À ajuster si SingPay documente un schéma différent (header, encodage, format
 * "t=...,v1=..." à la Stripe). L'audit log capture chaque rejet pour diagnostic.
 */
class VerifySingPayWebhookSignature
{
    public function __construct(protected AuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.singpay.webhook_secret');

        if (! $secret) {
            $this->audit->record('webhook.signature_failed', null, [
                'reason' => 'missing_secret_config',
                'headers' => $request->headers->keys(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook secret non configuré.',
            ], 503);
        }

        $provided = (string) $request->header('X-SingPay-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            $this->audit->record('webhook.signature_failed', null, [
                'reason' => $provided === '' ? 'missing_header' : 'mismatch',
                'reference' => $request->input('reference'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Signature invalide.',
            ], 401);
        }

        return $next($request);
    }
}
