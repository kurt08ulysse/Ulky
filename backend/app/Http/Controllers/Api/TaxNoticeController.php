<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxNoticeRequest;
use App\Http\Resources\TaxNoticeResource;
use App\Models\Tax;
use App\Models\TaxNotice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaxNoticeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', TaxNotice::class);

        $user = auth()->user();
        $query = TaxNotice::with(['tax', 'user']);

        // Filtrage de sécurité : un citoyen ne peut voir que ses propres avis
        if ($user->hasRole('citizen')) {
            $query->where('user_id', $user->id);
        } else {
            // Filtrage optionnel pour les agents (par statut ou par contribuable)
            if ($request->has('user_id')) {
                $query->where('user_id', $request->integer('user_id'));
            }
        }

        // Filtrage par statut
        if ($request->has('status') && in_array($request->string('status'), ['pending', 'paid', 'cancelled'])) {
            $query->where('status', $request->string('status'));
        }

        $notices = $query->orderBy('due_date', 'desc')->get();

        return TaxNoticeResource::collection($notices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaxNoticeRequest $request): JsonResponse
    {
        Gate::authorize('create', TaxNotice::class);

        $validated = $request->validated();
        $tax = Tax::findOrFail($validated['tax_id']);

        // Héritage des montants de la taxe si non spécifiés dans la requête
        $validated['base_amount'] = $validated['base_amount'] ?? $tax->base_amount;
        $validated['stamp_amount'] = $validated['stamp_amount'] ?? $tax->stamp_amount;
        $validated['status'] = 'pending';

        $taxNotice = TaxNotice::create($validated);

        return (new TaxNoticeResource($taxNotice->load(['tax', 'user'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TaxNotice $taxNotice): TaxNoticeResource
    {
        Gate::authorize('view', $taxNotice);

        return new TaxNoticeResource($taxNotice->load(['tax', 'user']));
    }

    /**
     * Cancel the specified tax notice.
     */
    public function cancel(TaxNotice $taxNotice): TaxNoticeResource
    {
        Gate::authorize('cancel', $taxNotice);

        $taxNotice->update([
            'status' => 'cancelled'
        ]);

        return new TaxNoticeResource($taxNotice->load(['tax', 'user']));
    }
}
