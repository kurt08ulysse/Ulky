<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Réconciliation quotidienne des paiements (Phase 3 — régie de recettes).
// Tout écart fait échouer la tâche planifiée → alerte de supervision.
Schedule::command('payments:reconcile')
    ->dailyAt('02:00')
    ->withoutOverlapping();
