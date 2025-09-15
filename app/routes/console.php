<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// Register class-based commands
//Artisan::starting(function ($artisan) {
//    $artisan->resolve(\App\Console\Commands\TestFastApi::class);
//});

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
