<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StallRentResource;
use App\Models\StallRent;
use App\Services\SingPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Loyers d'emplacement de marché — côté commerçant.
 *
 * Isolation stricte : un commerçant ne voit et ne paie QUE ses propres loyers.
 * Le staff municipal voit tout (cloisonné par commune, sauf super_admin).
 */
class StallRentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', StallRent::class);

        $user = auth()->user();
        $query = StallRent::with(['stall.market', 'occupant', 'payment.receipt']);

        $isStaff = $user->hasAnyRole(['municipal_agent', 'cashier', 'commune_admin', 'super_admin']);

        if ($isStaff) {
            // Cloisonnement multi-commune (super_admin : global).
            if (! $user->hasRole('super_admin')) {
                $query->where('commune_id', $user->commune_id);
            }
            if ($request->has('occupant_id')) {
                $query->where('occupant_id', $request->integer('occupant_id'));
            }
        } else {
            // Commerçant : uniquement ses propres loyers.
            $query->where('occupant_id', $user->id);
        }

        if ($request->has('status') && in_array($request->string('status')->toString(), ['pending', 'paid', 'cancelled', 'late'], true)) {
            $query->where('status', $request->string('status')->toString());
        }

        return StallRentResource::collection($query->orderBy('due_date', 'desc')->get());
    }

    public function show(StallRent $stallRent): StallRentResource
    {
        Gate::authorize('view', $stallRent);

        return new StallRentResource($stallRent->load(['stall.market', 'occupant', 'payment.receipt']));
    }

    /**
     * Initie le paiement Mobile Money d'un loyer via SingPay (même rail que les taxes).
     */
    public function pay(StallRent $stallRent, Request $request, SingPayService $singPayService): JsonResponse
    {
        Gate::authorize('view', $stallRent);

        if ($stallRent->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ce loyer ne peut pas être payé car son statut est : '.$stallRent->status,
            ], 422);
        }

        $validated = $request->validate([
            'operator' => 'required|in:airtel_money,moov_money',
            'phone' => 'required|string',
        ]);

        $payment = $stallRent->payments()->create([
            'amount' => $stallRent->total_amount,
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
                'amount_formatted' => $stallRent->amount_formatted,
            ],
        ]);
    }
}
