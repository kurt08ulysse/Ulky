<?php

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
    ->middleware('throttle:60,1')
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
