<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Podado Automático: Eliminar notificaciones de base de datos con más de 30 días de antigüedad
Schedule::call(function () {
    DB::table('notifications')
        ->where('created_at', '<', now()->subDays(30))
        ->delete();
})->dailyAt('03:00')->name('prune-notifications')->withoutOverlapping();
