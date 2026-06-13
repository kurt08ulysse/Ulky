<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesAttachments;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\CitizenReportResource;
use App\Models\CitizenReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Signalements citoyens.
 *
 * Isolation stricte : un citoyen ne voit et ne suit QUE ses propres signalements.
 * Le staff municipal voit tout (cloisonné par commune) et fait évoluer le statut.
 */
class CitizenReportController extends Controller
{
    use HandlesAttachments;

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', CitizenReport::class);

        $user = auth()->user();
        $query = CitizenReport::with(['events', 'attachments'])->orderBy('created_at', 'desc');

        $isStaff = $user->hasAnyRole(['municipal_agent', 'cashier', 'commune_admin', 'super_admin']);

        if ($isStaff) {
            if (! $user->hasRole('super_admin')) {
                $query->where('commune_id', $user->commune_id);
            }
            if ($request->has('status') && in_array($request->string('status')->toString(), CitizenReport::STATUSES, true)) {
                $query->where('status', $request->string('status')->toString());
            }
            if ($request->has('category')) {
                $query->where('category', $request->string('category')->toString());
            }
        } else {
            $query->where('user_id', $user->id);
        }

        return CitizenReportResource::collection($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', CitizenReport::class);

        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', CitizenReport::CATEGORIES),
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();

        $report = CitizenReport::create([
            'user_id' => $user->id,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => 'new',
            'commune_id' => $user->commune_id,
        ]);

        return (new CitizenReportResource($report->load(['events', 'attachments'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(CitizenReport $citizenReport): CitizenReportResource
    {
        Gate::authorize('view', $citizenReport);

        return new CitizenReportResource($citizenReport->load(['events', 'attachments']));
    }

    /**
     * Fait évoluer un signalement (staff) : statut + note + assignation éventuelle.
     */
    public function transition(CitizenReport $citizenReport, Request $request): JsonResponse
    {
        Gate::authorize('transition', $citizenReport);

        $validated = $request->validate([
            'to_status' => 'required|string|in:'.implode(',', CitizenReport::STATUSES),
            'note' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        if (! $citizenReport->canTransitionTo($validated['to_status'])) {
            return response()->json([
                'success' => false,
                'message' => "Transition non autorisée : {$citizenReport->status} → {$validated['to_status']}.",
            ], 422);
        }

        if (array_key_exists('assigned_to', $validated) && $validated['assigned_to'] !== null) {
            $citizenReport->assigned_to = $validated['assigned_to'];
        }

        $citizenReport->transitionNote = $validated['note'] ?? null;
        $citizenReport->status = $validated['to_status'];
        $citizenReport->save();

        return response()->json([
            'success' => true,
            'message' => 'Signalement mis à jour.',
            'data' => new CitizenReportResource($citizenReport->load(['events', 'attachments'])),
        ]);
    }

    /**
     * Ajoute une pièce jointe (photo/PDF) à son propre signalement.
     */
    public function attach(CitizenReport $citizenReport, Request $request): JsonResponse
    {
        Gate::authorize('view', $citizenReport);

        $attachment = $this->storeAttachment($citizenReport, $request);

        return (new AttachmentResource($attachment))->response()->setStatusCode(201);
    }
}
