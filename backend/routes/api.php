<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\SingPayWebhookController;
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
Route::prefix('v1')->middleware('clerk.auth')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Gestion du référentiel des taxes et des contribuables
    Route::apiResource('taxes', TaxController::class);
    Route::apiResource('tax-notices', TaxNoticeController::class)->except(['destroy', 'update']);
    Route::put('tax-notices/{taxNotice}/cancel', [TaxNoticeController::class, 'cancel']);
    Route::post('tax-notices/{taxNotice}/pay', [TaxNoticeController::class, 'pay']);

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

        // Journal d'audit
        Route::get('audit-logs', [AdminController::class, 'auditLogs']);

        // Export CSV
        Route::get('export/csv', [AdminController::class, 'exportCsv']);
    });
