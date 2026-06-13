<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Dépenses de la commune — réservées au personnel financier (régisseur/admin).
 * Cloisonnées par commune (super_admin : global).
 */
class ExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Expense::class);

        $query = Expense::query()->orderBy('spent_at', 'desc');
        $this->scopeToCommune($query);

        if ($request->has('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        $perPage = min((int) $request->integer('per_page', 30), 100);

        return ExpenseResource::collection($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Expense::class);

        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', Expense::CATEGORIES),
            'label' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'spent_at' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $user = auth()->user();

        $expense = Expense::create([
            'category' => $validated['category'],
            'label' => $validated['label'],
            'amount' => $validated['amount'],
            'spent_at' => $validated['spent_at'] ?? Carbon::today()->toDateString(),
            'note' => $validated['note'] ?? null,
            'recorded_by' => $user->id,
            'commune_id' => $user->commune_id,
        ]);

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }

    /** Cloisonnement commune (super_admin : accès global). */
    private function scopeToCommune($query): void
    {
        $user = auth()->user();
        if (! $user->hasRole('super_admin')) {
            $query->where('commune_id', $user->commune_id);
        }
    }
}
