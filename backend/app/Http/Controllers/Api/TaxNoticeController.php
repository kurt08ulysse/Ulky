<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxNoticeRequest;
use App\Http\Resources\TaxNoticeResource;
use App\Models\Tax;
use App\Models\TaxNotice;
use App\Services\SingPayService;
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

        // Filtrage de sécurité (deny-by-default) : seuls les agents/admins de la
        // mairie voient les avis des autres contribuables. Tout autre utilisateur
        // — citoyen, commerçant, ou compte sans rôle — est strictement limité à
        // ses propres avis (isolation par utilisateur, façon « chacun sa boîte »).
        $isStaff = $user->hasAnyRole(['municipal_agent', 'cashier', 'commune_admin', 'super_admin']);

        if ($isStaff) {
            // Filtrage optionnel pour les agents (par contribuable)
            if ($request->has('user_id')) {
                $query->where('user_id', $request->integer('user_id'));
            }
        } else {
            $query->where('user_id', $user->id);
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
            'status' => 'cancelled',
        ]);

        return new TaxNoticeResource($taxNotice->load(['tax', 'user']));
    }

    /**
     * Initiate mobile money payment for the tax notice via SingPay.
     */
    public function pay(TaxNotice $taxNotice, Request $request, SingPayService $singPayService): JsonResponse
    {
        Gate::authorize('view', $taxNotice);

        if ($taxNotice->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cet avis de taxe ne peut pas être payé car son statut est : '.$taxNotice->status,
            ], 422);
        }

        $validated = $request->validate([
            'operator' => 'required|in:airtel_money,moov_money',
            'phone' => 'required|string',
        ]);

        // Crée l'enregistrement de paiement local (rattaché polymorphiquement à l'avis)
        $payment = $taxNotice->payments()->create([
            'amount' => $taxNotice->total_amount,
            'operator' => $validated['operator'],
            'phone' => $validated['phone'],
            'status' => 'pending',
        ]);

        // Appel de l'intégration SingPay
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
            'message' => 'Paiement initié avec succès. Veuillez valider le code USSD / push sur votre téléphone.',
            'payment' => [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
                'amount_formatted' => $taxNotice->total_amount_formatted,
            ],
        ]);
    }
}
