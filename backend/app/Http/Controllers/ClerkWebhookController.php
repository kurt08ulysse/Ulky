<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Svix\Exception\WebhookVerificationException;
use Svix\Webhook;

/**
 * Gestionnaire des webhooks Clerk.
 *
 * Route : POST /webhooks/clerk
 * - Exclue du middleware auth:clerk (pas de JWT utilisateur dans un webhook)
 * - Signature Svix vérifiée (CLERK_WEBHOOK_SECRET)
 * - Handlers idempotents : un webhook rejoué ne crée jamais de doublon
 * - user.deleted → anonymisation uniquement, jamais suppression
 *   (quittances et journal d'audit append-only, cf. PLAN.MD phase 3)
 */
class ClerkWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Vérification de la signature Svix
        $secret = config('services.clerk.webhook_secret');

        if (! $secret) {
            Log::error('ClerkWebhook: CLERK_WEBHOOK_SECRET non configuré');

            return response()->json(['message' => 'Configuration error.'], 500);
        }

        try {
            $wh = new Webhook($secret);
            $payload = $wh->verify(
                $request->getContent(),
                [
                    'svix-id' => $request->header('svix-id', ''),
                    'svix-timestamp' => $request->header('svix-timestamp', ''),
                    'svix-signature' => $request->header('svix-signature', ''),
                ]
            );
        } catch (WebhookVerificationException $e) {
            Log::warning('ClerkWebhook: signature invalide — '.$e->getMessage());

            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $eventType = $payload['type'] ?? null;
        $data = $payload['data'] ?? [];

        Log::info("ClerkWebhook: événement reçu — {$eventType}");

        match ($eventType) {
            'user.created' => $this->handleUserCreated($data),
            'user.updated' => $this->handleUserUpdated($data),
            'user.deleted' => $this->handleUserDeleted($data),
            default => null, // événements inconnus ignorés silencieusement
        };

        // Toujours retourner 200 : Svix rejoue sur 4xx/5xx
        return response()->json(['data' => null, 'meta' => []]);
    }

    /**
     * user.created — crée le miroir local.
     * Idempotent : updateOrCreate sur clerk_id.
     */
    private function handleUserCreated(array $data): void
    {
        $clerkId = $data['id'] ?? null;
        if (! $clerkId) {
            return;
        }

        $user = User::updateOrCreate(
            ['clerk_id' => $clerkId],
            $this->extractUserAttributes($data)
        );

        // Assigner le rôle citoyen par défaut si pas encore de rôle
        if ($user->wasRecentlyCreated && $user->roles->isEmpty()) {
            $user->assignRole('citizen');
        }

        Log::info("ClerkWebhook: miroir créé/mis à jour pour clerk_id={$clerkId}");
    }

    /**
     * user.updated — met à jour le miroir local.
     * Idempotent : updateOrCreate sur clerk_id.
     */
    private function handleUserUpdated(array $data): void
    {
        $clerkId = $data['id'] ?? null;
        if (! $clerkId) {
            return;
        }

        User::updateOrCreate(
            ['clerk_id' => $clerkId],
            $this->extractUserAttributes($data)
        );

        Log::info("ClerkWebhook: miroir mis à jour pour clerk_id={$clerkId}");
    }

    /**
     * user.deleted — anonymise, ne supprime JAMAIS.
     * Les quittances et le journal d'audit (phase 3) référencent ce user_id.
     * RGPD/CNPDCP : les données fiscales sont conservées pour les obligations légales.
     */
    private function handleUserDeleted(array $data): void
    {
        $clerkId = $data['id'] ?? null;
        if (! $clerkId) {
            return;
        }

        $user = User::where('clerk_id', $clerkId)->first();
        if (! $user) {
            return;
        }

        $user->update([
            'name' => '[compte supprimé]',
            'email' => null,
            'phone' => null,
            'clerk_id' => null, // libère l'unicité pour les éventuelles recréations
        ]);

        Log::info("ClerkWebhook: user anonymisé pour clerk_id={$clerkId}");
    }

    /**
     * Extraire les attributs User depuis la payload Clerk.
     */
    private function extractUserAttributes(array $data): array
    {
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $name = trim("{$firstName} {$lastName}") ?: 'Utilisateur';

        $email = $data['email_addresses'][0]['email_address'] ?? null;
        $phone = $data['phone_numbers'][0]['phone_number'] ?? null;

        return compact('name', 'email', 'phone');
    }
}
