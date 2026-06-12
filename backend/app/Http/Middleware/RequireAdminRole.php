<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware vérifiant que l'utilisateur authentifié possède un rôle admin.
 *
 * Rôles autorisés : municipal_agent, cashier, commune_admin, super_admin.
 * Les citoyens reçoivent une réponse 403 — l'onglet Admin n'est pas rendu
 * côté frontend, mais ce middleware constitue la deuxième ligne de défense.
 */
class RequireAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || ! $user->hasAnyRole(['municipal_agent', 'cashier', 'commune_admin', 'super_admin'])) {
            return response()->json([
                'message' => 'Accès refusé. Rôle insuffisant.',
            ], 403);
        }

        return $next($request);
    }
}
