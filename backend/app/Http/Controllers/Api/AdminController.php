<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminTaxNoticeResource;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Tax;
use App\Models\TaxNotice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contrôleur du tableau de bord administratif mairie.
 *
 * Toutes ces routes sont protégées par :
 *   - clerk.auth  (JWT Clerk valide)
 *   - admin.role  (RequireAdminRole : municipal_agent | cashier | commune_admin | super_admin)
 *
 * Les citoyens reçoivent 403 au niveau du middleware — ils n'atteignent jamais ce contrôleur.
 */
class AdminController extends Controller
{
    /* -----------------------------------------------------------------------
     * 1. KPIs du dashboard
     * -------------------------------------------------------------------- */

    /**
     * GET /api/v1/admin/dashboard
     *
     * Retourne les indicateurs clés : encaissements, nombre d'avis, top taxes.
     */
    public function dashboard(): JsonResponse
    {
        $today     = Carbon::today();
        $thisWeek  = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        // Encaissements en centimes (paiements successful uniquement)
        $collectedToday  = Payment::successful()->whereDate('created_at', $today)->sum('amount');
        $collectedWeek   = Payment::successful()->where('created_at', '>=', $thisWeek)->sum('amount');
        $collectedMonth  = Payment::successful()->where('created_at', '>=', $thisMonth)->sum('amount');
        $collectedTotal  = Payment::successful()->sum('amount');

        // Nombre d'avis par statut
        $noticesByStatus = TaxNotice::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Top 5 taxes les plus perçues ce mois
        $topTaxes = TaxNotice::with('tax:id,name')
            ->where('status', 'paid')
            ->where('paid_at', '>=', $thisMonth)
            ->select('tax_id', DB::raw('count(*) as count'), DB::raw('sum(base_amount + stamp_amount) as total'))
            ->groupBy('tax_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'tax_name' => $row->tax?->name ?? '—',
                'count'    => $row->count,
                'total'    => $row->total,
                'total_formatted' => number_format($row->total / 100, 0, ',', ' ').' FCFA',
            ]);

        // Encaissements par jour sur les 7 derniers jours (pour le graphique)
        $last7Days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));
        $dailyData = Payment::successful()
            ->where('created_at', '>=', Carbon::today()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('sum(amount) as total'))
            ->groupBy('day')
            ->pluck('total', 'day');

        $chartData = $last7Days->map(fn ($day) => [
            'date'  => $day->toDateString(),
            'label' => $day->locale('fr')->isoFormat('ddd D'),
            'total' => (int) ($dailyData[$day->toDateString()] ?? 0),
        ])->values();

        return response()->json([
            'collected' => [
                'today'   => ['amount' => $collectedToday,  'formatted' => number_format($collectedToday / 100, 0, ',', ' ').' FCFA'],
                'week'    => ['amount' => $collectedWeek,   'formatted' => number_format($collectedWeek / 100, 0, ',', ' ').' FCFA'],
                'month'   => ['amount' => $collectedMonth,  'formatted' => number_format($collectedMonth / 100, 0, ',', ' ').' FCFA'],
                'total'   => ['amount' => $collectedTotal,  'formatted' => number_format($collectedTotal / 100, 0, ',', ' ').' FCFA'],
            ],
            'notices_by_status' => [
                'pending'   => (int) ($noticesByStatus['pending']   ?? 0),
                'paid'      => (int) ($noticesByStatus['paid']      ?? 0),
                'cancelled' => (int) ($noticesByStatus['cancelled'] ?? 0),
            ],
            'top_taxes'  => $topTaxes,
            'chart_data' => $chartData,
        ]);
    }

    /* -----------------------------------------------------------------------
     * 2. Liste paginée de tous les avis (tous citoyens)
     * -------------------------------------------------------------------- */

    /**
     * GET /api/v1/admin/tax-notices
     *
     * Paramètres de filtre : status, search (nom/téléphone/n° quittance), date_from, date_to
     * Pagination : ?page=&per_page=
     */
    public function taxNotices(Request $request): AnonymousResourceCollection
    {
        $query = TaxNotice::with(['tax', 'user', 'payment', 'payment.receipt'])
            ->orderBy('created_at', 'desc');

        // Filtre par statut
        if ($status = $request->string('status')->toString()) {
            if (in_array($status, ['pending', 'paid', 'cancelled'])) {
                $query->where('status', $status);
            }
        }

        // Filtre plage de dates (sur created_at)
        if ($from = $request->string('date_from')->toString()) {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $request->string('date_to')->toString()) {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        // Recherche : nom contribuable, téléphone, ou numéro de quittance
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                // Nom ou téléphone du citoyen
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'ilike', "%{$search}%")
                       ->orWhere('phone', 'ilike', "%{$search}%");
                });

                // Numéro de quittance (via payment → receipt)
                $q->orWhereHas('payment.receipt', function ($rq) use ($search) {
                    $rq->where('receipt_number', 'ilike', "%{$search}%");
                });
            });
        }

        $perPage = min((int) $request->integer('per_page', 20), 100);
        $paginated = $query->paginate($perPage);

        return AdminTaxNoticeResource::collection($paginated);
    }

    /* -----------------------------------------------------------------------
     * 3. Détail d'un avis
     * -------------------------------------------------------------------- */

    /**
     * GET /api/v1/admin/tax-notices/{taxNotice}
     */
    public function showTaxNotice(TaxNotice $taxNotice): AdminTaxNoticeResource
    {
        return new AdminTaxNoticeResource(
            $taxNotice->load(['tax', 'user', 'payment', 'payment.receipt'])
        );
    }

    /* -----------------------------------------------------------------------
     * 4. Créer un avis pour un citoyen
     * -------------------------------------------------------------------- */

    /**
     * POST /api/v1/admin/tax-notices
     *
     * Body: { tax_id, user_id, due_date?, base_amount?, stamp_amount? }
     * L'agent saisit le numéro de téléphone et on résout le user_id.
     */
    public function createTaxNotice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tax_id'       => 'required|integer|exists:taxes,id',
            'phone'        => 'required|string',
            'due_date'     => 'nullable|date|after_or_equal:today',
            'base_amount'  => 'nullable|integer|min:0',
            'stamp_amount' => 'nullable|integer|min:0',
        ]);

        // Résolution du citoyen via le téléphone
        $citizen = User::where('phone', $validated['phone'])->first();
        if (! $citizen) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun contribuable trouvé avec ce numéro de téléphone.',
            ], 404);
        }

        $tax = Tax::findOrFail($validated['tax_id']);

        $notice = TaxNotice::create([
            'tax_id'       => $tax->id,
            'user_id'      => $citizen->id,
            'base_amount'  => $validated['base_amount']  ?? $tax->base_amount,
            'stamp_amount' => $validated['stamp_amount'] ?? $tax->stamp_amount,
            'due_date'     => $validated['due_date'] ?? Carbon::now()->addDays(30)->toDateString(),
            'status'       => 'pending',
            'commune_id'   => $citizen->commune_id,
        ]);

        return (new AdminTaxNoticeResource($notice->load(['tax', 'user'])))
            ->response()
            ->setStatusCode(201);
    }

    /* -----------------------------------------------------------------------
     * 5. Recherche d'un citoyen par téléphone (debounce côté client)
     * -------------------------------------------------------------------- */

    /**
     * GET /api/v1/admin/citizens/search?phone=XXXX
     */
    public function searchCitizen(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string|min:4']);

        $citizens = User::where('phone', 'ilike', '%'.$request->string('phone').'%')
            ->select(['id', 'name', 'phone', 'email', 'taxpayer_type', 'commune_id'])
            ->limit(10)
            ->get();

        return response()->json(['data' => $citizens]);
    }

    /* -----------------------------------------------------------------------
     * 6. Journal d'audit
     * -------------------------------------------------------------------- */

    /**
     * GET /api/v1/admin/audit-logs
     *
     * Paramètres : action (filtre), date_from, date_to, page, per_page
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::orderBy('created_at', 'desc');

        if ($action = $request->string('action')->toString()) {
            $query->where('action', $action);
        }
        if ($from = $request->string('date_from')->toString()) {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $request->string('date_to')->toString()) {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $perPage = min((int) $request->integer('per_page', 30), 100);
        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    /* -----------------------------------------------------------------------
     * 7. Export CSV
     * -------------------------------------------------------------------- */

    /**
     * GET /api/v1/admin/export/csv
     *
     * Paramètres : date_from, date_to, status
     * Retourne un fichier CSV en téléchargement direct.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $from   = $request->string('date_from')->toString() ?: Carbon::now()->startOfMonth()->toDateString();
        $to     = $request->string('date_to')->toString()   ?: Carbon::now()->toDateString();
        $status = $request->string('status')->toString();

        $query = TaxNotice::with(['tax', 'user', 'payment'])
            ->whereBetween('created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ]);

        if ($status && in_array($status, ['pending', 'paid', 'cancelled'])) {
            $query->where('status', $status);
        }

        $notices = $query->orderBy('created_at', 'desc')->get();

        $filename = 'recettes_mairie_'.$from.'_'.$to.'.csv';

        return response()->streamDownload(function () use ($notices) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour Excel (Windows)
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'N° Avis',
                'Date création',
                'Contribuable',
                'Téléphone',
                'Type de contribuable',
                'Type de taxe',
                'Montant base (FCFA)',
                'Timbre fiscal (FCFA)',
                'Total (FCFA)',
                'Statut',
                'Date paiement',
                'Opérateur Mobile Money',
                'Réf. transaction SingPay',
                'N° Quittance',
            ], separator: ';');

            foreach ($notices as $notice) {
                fputcsv($handle, [
                    $notice->id,
                    $notice->created_at?->format('d/m/Y H:i'),
                    $notice->user?->name ?? '—',
                    $notice->user?->phone ?? '—',
                    $notice->user?->taxpayer_type ?? '—',
                    $notice->tax?->name ?? '—',
                    number_format($notice->base_amount / 100, 0, ',', ' '),
                    number_format($notice->stamp_amount / 100, 0, ',', ' '),
                    number_format($notice->total_amount / 100, 0, ',', ' '),
                    $notice->status,
                    $notice->paid_at?->format('d/m/Y H:i') ?? '—',
                    $notice->payment?->operator ?? '—',
                    $notice->payment?->transaction_id ?? '—',
                    $notice->payment?->receipt?->receipt_number ?? '—',
                ], separator: ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
