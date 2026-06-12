<?php

use App\Http\Controllers\Api\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verify/receipt/{token}', [ReceiptController::class, 'verify'])
    ->name('receipts.verify');
