<?php

use App\Http\Controllers\SlackController;
use App\Http\Middleware\VerifySlackSignature;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Slack signe ses requêtes (VerifySlackSignature) mais n'envoie pas de jeton CSRF.
Route::prefix('slack')
    ->name('slack.')
    ->middleware(VerifySlackSignature::class)
    ->withoutMiddleware(PreventRequestForgery::class)
    ->controller(SlackController::class)
    ->group(function () {
        Route::post('command', 'command')->name('command');
        Route::post('interact', 'interact')->name('interact');
    });
