<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * AuthController — uniquement les endpoints liés à la session locale.
 *
 * Clerk porte l'authentification (inscription, connexion, réinitialisation).
 * Aucun endpoint login/register/reset ne vit ici — cf. AGENTS.md.
 *
 * Le guard ClerkAuthenticate (middleware) vérifie le JWT à chaque requête
 * et injecte l'utilisateur via auth()->setUser().
 */
class AuthController extends Controller
{
    /**
     * Retourne le profil de l'utilisateur connecté.
     * Le guard ClerkAuthenticate a déjà résolu $request->user().
     */
    public function me(Request $request)
    {
        return response()->json([
            'data' => new UserResource($request->user()),
            'meta' => [],
        ]);
    }

    /**
     * Déconnexion locale.
     * Le frontend appelle signOut() depuis le SDK Clerk pour invalider la session Clerk.
     * Côté Laravel, il n'y a pas de token à supprimer (guard JWT stateless).
     * Cet endpoint est conservé pour permettre au frontend d'avoir un point de terminaison
     * symétrique et pour les éventuels effets de bord futurs (blacklist, audit log…).
     */
    public function logout(Request $request)
    {
        return response()->json([
            'data' => null,
            'meta' => [],
        ]);
    }
}
