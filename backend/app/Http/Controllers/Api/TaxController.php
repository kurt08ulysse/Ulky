<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxRequest;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Tax::class);

        $taxes = Tax::orderBy('name')->get();

        return TaxResource::collection($taxes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaxRequest $request): JsonResponse
    {
        Gate::authorize('create', Tax::class);

        $tax = Tax::create($request->validated());

        return (new TaxResource($tax))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tax $tax): TaxResource
    {
        Gate::authorize('view', $tax);

        return new TaxResource($tax);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTaxRequest $request, Tax $tax): TaxResource
    {
        Gate::authorize('update', $tax);

        $tax->update($request->validated());

        return new TaxResource($tax);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tax $tax): JsonResponse
    {
        Gate::authorize('delete', $tax);

        $tax->delete();

        return response()->json([
            'message' => 'Taxe supprimée avec succès.',
        ]);
    }
}
