<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard Clerk natif.
 *
 * Chaque requête protégée doit présenter un JWT Clerk valide dans :
 *   Authorization: Bearer <session_token>
 *
 * Le token est vérifié à la volée via JWKS (mis en cache 5 min).
 * Aucun token Sanctum n'est émis ni stocké.
 *
 * Sécurité :
 * - Signature RS256 vérifiée via JWKS
 * - exp vérifié par firebase/php-jwt
 * - azp (authorized party) vérifié si CLERK_AUTHORIZED_PARTY est défini
 * - clerk_id (sub) est la clé de liaison vers le miroir local
 *
 * Tests :
 * - En CI, injecter CLERK_TESTING_JWKS (JSON) pour signer des JWT locaux
 *   sans appel réseau à clerk.com.
 */
class ClerkAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $keys = $this->resolveKeys();
            $decoded = JWT::decode($token, $keys);
        } catch (\Throwable $e) {
            Log::warning('ClerkAuthenticate: JWT invalide — '.$e->getMessage());

            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Vérification de l'authorized party (optionnel mais recommandé)
        $authorizedParty = config('services.clerk.authorized_party');
        if ($authorizedParty && ($decoded->azp ?? null) !== $authorizedParty) {
            Log::warning('ClerkAuthenticate: azp invalide — '.$decoded->azp ?? 'absent');

            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $clerkId = $decoded->sub ?? null;
        if (! $clerkId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Filet find-or-create : si le webhook Clerk n'est pas encore passé,
        // on crée le miroir local à partir des claims du JWT ou de l'API Clerk.
        $user = User::where('clerk_id', $clerkId)->first()
            ?? $this->provisionUser($clerkId, $decoded);

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Injecter l'utilisateur dans le contexte de la requête
        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    /**
     * Résoudre les clés JWKS.
     *
     * Mode test HS256 : si CLERK_TESTING_SECRET est défini, on retourne une clé symétrique.
     * Cela évite toute dépendance à openssl_pkey_new() ou à un fichier openssl.cnf.
     *
     * Mode production : JWKS RS256 récupérés depuis Clerk, mis en cache 5 minutes.
     */
    private function resolveKeys(): Key|array
    {
        // Les modes de test (HS256 symétrique ou JWKS injectés) ne sont JAMAIS
        // honorés en production : défense en profondeur contre une mauvaise
        // configuration qui activerait un bypass de signature.
        if (! app()->environment('production')) {
            // Mode test symétrique (HS256) — injecté via CLERK_TESTING_SECRET
            $testingSecret = config('services.clerk.testing_secret');
            if ($testingSecret) {
                return new Key($testingSecret, 'HS256');
            }

            // Mode test JWKS (RS256) — injecté via CLERK_TESTING_JWKS (JSON)
            $testingJwks = config('services.clerk.testing_jwks');
            if ($testingJwks) {
                return JWK::parseKeySet(json_decode($testingJwks, true));
            }
        }

        // Production : JWKS récupérés depuis Clerk, mis en cache 5 minutes
        $jwks = Cache::remember('clerk_jwks', 300, function () {
            $response = Http::timeout(5)->get(config('services.clerk.jwks_url'));

            if ($response->failed()) {
                throw new \RuntimeException('Impossible de récupérer les JWKS Clerk.');
            }

            return $response->json();
        });

        return JWK::parseKeySet($jwks);
    }

    /**
     * Filet find-or-create.
     * Appelé uniquement si le webhook Clerk n'a pas encore créé le miroir local.
     * Tente d'appeler l'API Clerk pour récupérer les données du user.
     */
    private function provisionUser(string $clerkId, object $claims): ?User
    {
        try {
            $response = Http::timeout(5)
                ->withToken(config('services.clerk.secret_key'))
                ->get("https://api.clerk.com/v1/users/{$clerkId}");

            if ($response->failed()) {
                Log::warning("ClerkAuthenticate: impossible de récupérer user Clerk {$clerkId}");

                return null;
            }

            $data = $response->json();
            $email = $data['email_addresses'][0]['email_address'] ?? null;
            $phone = $data['phone_numbers'][0]['phone_number'] ?? null;
            $name = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')) ?: 'Utilisateur';

            // firstOrCreate : idempotent sous charge. Si le même utilisateur ouvre
            // plusieurs appareils simultanément (ou si le webhook arrive en parallèle),
            // on ne déclenche pas de violation de contrainte d'unicité sur clerk_id.
            /** @var User $user */
            $user = User::firstOrCreate(
                ['clerk_id' => $clerkId],
                [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                ]
            );

            // Rôle par défaut : citoyen (seulement à la création réelle)
            if ($user->wasRecentlyCreated && $user->roles->isEmpty()) {
                $user->assignRole('citizen');
            }

            Log::info("ClerkAuthenticate: miroir créé par filet pour clerk_id={$clerkId}");

            return $user;
        } catch (\Throwable $e) {
            Log::error("ClerkAuthenticate: erreur filet — {$e->getMessage()}");

            return null;
        }
    }
}
