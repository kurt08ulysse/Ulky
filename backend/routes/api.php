<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdministrativeRequestController;
use App\Http\Controllers\Api\CitizenReportController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\MarketStallController;
use App\Http\Controllers\Api\OfficialController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\SingPayWebhookController;
use App\Http\Controllers\Api\StallRentController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\TaxNoticeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClerkWebhookController;
use Illuminate\Support\Facades\Route;

/*
 * Routes webhook — exclues du guard Clerk (pas de JWT utilisateur dans un webhook Clerk).
 * Throttlées séparément pour limiter les abus.
 */
Route::post('webhooks/clerk', [ClerkWebhookController::class, 'handle'])
    ->middleware('throttle:60,1');

Route::post('webhooks/singpay', [SingPayWebhookController::class, 'handle'])
    ->middleware(['throttle:60,1', 'singpay.signed'])
    ->name('singpay.webhook');

/*
 * API v1 — toutes les routes protégées par le guard Clerk natif.
 * Le middleware ClerkAuthenticate vérifie le JWT à chaque requête.
 */
Route::prefix('v1')->middleware(['clerk.auth', 'throttle:120,1'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Gestion du référentiel des taxes et des contribuables
    Route::apiResource('taxes', TaxController::class);
    Route::apiResource('tax-notices', TaxNoticeController::class)->except(['destroy', 'update']);
    Route::put('tax-notices/{taxNotice}/cancel', [TaxNoticeController::class, 'cancel']);
    Route::post('tax-notices/{taxNotice}/pay', [TaxNoticeController::class, 'pay']);

    // Marchés municipaux — côté commerçant (isolation par occupant)
    Route::get('my/stalls', [MarketStallController::class, 'mine']);
    Route::get('my/rents', [StallRentController::class, 'index']);
    Route::get('rents/{stallRent}', [StallRentController::class, 'show']);
    Route::post('rents/{stallRent}/pay', [StallRentController::class, 'pay']);

    // Demandes administratives (isolation par citoyen ; transitions réservées au staff)
    Route::get('requests', [AdministrativeRequestController::class, 'index']);
    Route::post('requests', [AdministrativeRequestController::class, 'store']);
    Route::get('requests/{administrativeRequest}', [AdministrativeRequestController::class, 'show']);
    Route::post('requests/{administrativeRequest}/transition', [AdministrativeRequestController::class, 'transition']);
    Route::post('requests/{administrativeRequest}/pay', [AdministrativeRequestController::class, 'pay']);

    // Signalements citoyens (isolation par citoyen ; traitement réservé au staff)
    Route::get('reports', [CitizenReportController::class, 'index']);
    Route::post('reports', [CitizenReportController::class, 'store']);
    Route::get('reports/{citizenReport}', [CitizenReportController::class, 'show']);
    Route::post('reports/{citizenReport}/transition', [CitizenReportController::class, 'transition']);

    // Élus de la commune (présentationnel, lecture seule)
    Route::get('officials', [OfficialController::class, 'index']);

    // Quittances
    Route::get('receipts/{receipt}', [ReceiptController::class, 'download']);
});

/*
 * API v1 Admin — protégées par clerk.auth + admin.role (municipal_agent | cashier | commune_admin | super_admin).
 * Les citoyens reçoivent 403 au niveau du middleware, avant même d'atteindre le contrôleur.
 */
Route::prefix('v1/admin')
    ->middleware(['clerk.auth', 'admin.role', 'throttle:120,1'])
    ->group(function () {
        // Tableau de bord — KPIs et graphique
        Route::get('dashboard', [AdminController::class, 'dashboard']);

        // Avis de taxes (tous les contribuables)
        Route::get('tax-notices', [AdminController::class, 'taxNotices']);
        Route::get('tax-notices/{taxNotice}', [AdminController::class, 'showTaxNotice']);
        Route::post('tax-notices', [AdminController::class, 'createTaxNotice']);

        // Recherche de citoyen par téléphone (debounce côté client)
        Route::get('citizens/search', [AdminController::class, 'searchCitizen']);

        // Promotion d'un citoyen au statut commerçant (rôle + numéro)
        Route::post('merchants', [AdminController::class, 'promoteMerchant']);

        // Comptabilité — dépenses de la commune (régisseur/admin)
        Route::get('expenses', [ExpenseController::class, 'index']);
        Route::post('expenses', [ExpenseController::class, 'store']);

        // Journal d'audit
        Route::get('audit-logs', [AdminController::class, 'auditLogs']);

        // Export CSV
        Route::get('export/csv', [AdminController::class, 'exportCsv']);
    });
