<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ElectedOfficialResource;
use App\Models\ElectedOfficial;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Élus de la commune — lecture seule pour citoyens et commerçants.
 * Le contenu est géré par l'administration via le back-office.
 */
class OfficialController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $officials = ElectedOfficial::published()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ElectedOfficialResource::collection($officials);
    }
}
