<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdministrativeRequestResource;
use App\Models\AdministrativeRequest;
use App\Services\SingPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Demandes administratives.
 *
 * Isolation stricte : un citoyen ne voit et ne suit QUE ses propres demandes.
 * Le staff municipal voit l'ensemble (cloisonné par commune, sauf super_admin)
 * et fait évoluer le statut selon la machine à états.
 */
class AdministrativeRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AdministrativeRequest::class);

        $user = auth()->user();
        $query = AdministrativeRequest::with('events')->orderBy('created_at', 'desc');

        $isStaff = $user->hasAnyRole(['municipal_agent', 'cashier', 'commune_admin', 'super_admin']);

        if ($isStaff) {
            if (! $user->hasRole('super_admin')) {
                $query->where('commune_id', $user->commune_id);
            }
            if ($request->has('user_id')) {
                $query->where('user_id', $request->integer('user_id'));
            }
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status') && in_array($request->string('status')->toString(), AdministrativeRequest::STATUSES, true)) {
            $query->where('status', $request->string('status')->toString());
        }

        return AdministrativeRequestResource::collection($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', AdministrativeRequest::class);

        $validated = $request->validate([
            'type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $user = auth()->user();

        $administrativeRequest = AdministrativeRequest::create([
            'user_id' => $user->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'status' => 'submitted',
            'commune_id' => $user->commune_id,
        ]);

        return (new AdministrativeRequestResource($administrativeRequest->load('events')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AdministrativeRequest $administrativeRequest): AdministrativeRequestResource
    {
        Gate::authorize('view', $administrativeRequest);

        return new AdministrativeRequestResource($administrativeRequest->load('events'));
    }

    /**
     * Fait évoluer le statut d'une demande (staff uniquement), en respectant la
     * machine à états. Chaque transition est tracée automatiquement (model event).
     */
    public function transition(AdministrativeRequest $administrativeRequest, Request $request): JsonResponse
    {
        Gate::authorize('transition', $administrativeRequest);

        $validated = $request->validate([
            'to_status' => 'required|string|in:'.implode(',', AdministrativeRequest::STATUSES),
            'note' => 'nullable|string',
            // La mairie peut fixer/ajuster les frais de la démarche lors du traitement.
            'fee_amount' => 'nullable|integer|min:0',
        ]);

        if (! $administrativeRequest->canTransitionTo($validated['to_status'])) {
            return response()->json([
                'success' => false,
                'message' => "Transition non autorisée : {$administrativeRequest->status} → {$validated['to_status']}.",
            ], 422);
        }

        if (array_key_exists('fee_amount', $validated) && $validated['fee_amount'] !== null) {
            $administrativeRequest->fee_amount = $validated['fee_amount'];
        }

        // La note est portée par l'événement de transition (append-only) via le hook updating.
        $administrativeRequest->transitionNote = $validated['note'] ?? null;
        $administrativeRequest->status = $validated['to_status'];
        $administrativeRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour.',
            'data' => new AdministrativeRequestResource($administrativeRequest->load('events')),
        ]);
    }

    /**
     * Le demandeur règle les frais de sa démarche via SingPay (même rail → quittance).
     */
    public function pay(AdministrativeRequest $administrativeRequest, Request $request, SingPayService $singPayService): JsonResponse
    {
        Gate::authorize('view', $administrativeRequest);

        if ($administrativeRequest->fee_amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun frais à régler pour cette démarche.',
            ], 422);
        }

        if ($administrativeRequest->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Les frais de cette démarche ont déjà été réglés.',
            ], 422);
        }

        $validated = $request->validate([
            'operator' => 'required|in:airtel_money,moov_money',
            'phone' => 'required|string',
        ]);

        $payment = $administrativeRequest->payments()->create([
            'amount' => $administrativeRequest->total_amount,
            'operator' => $validated['operator'],
            'phone' => $validated['phone'],
            'status' => 'pending',
        ]);

        $result = $singPayService->initiatePayment($payment);

        if (! $result['success']) {
            $payment->update(['status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Échec de l\'initiation du paiement.',
            ], 422);
        }

        $payment->update([
            'transaction_id' => $result['transaction_id'] ?? null,
            'raw_response' => $result['raw'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement initié avec succès. Veuillez valider le push sur votre téléphone.',
            'payment' => [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
            ],
        ]);
    }
}
