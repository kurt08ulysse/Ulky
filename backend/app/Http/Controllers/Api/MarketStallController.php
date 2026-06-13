<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarketStallResource;
use App\Models\MarketStall;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MarketStallController extends Controller
{
    /**
     * Emplacements occupés par le commerçant connecté (isolation par occupant).
     */
    public function mine(): AnonymousResourceCollection
    {
        $stalls = MarketStall::with('market')
            ->where('occupant_id', auth()->id())
            ->orderBy('stall_number')
            ->get();

        return MarketStallResource::collection($stalls);
    }
}
