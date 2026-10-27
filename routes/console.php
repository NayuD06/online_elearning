<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:seed-if-empty', function () {
    if (User::query()->exists()) {
        $this->info('Database already has users, skipping seed.');

        return;
    }

    $this->call('db:seed', ['--force' => true]);
})->purpose('Seed demo data only when the database is empty (used on deploy)');
