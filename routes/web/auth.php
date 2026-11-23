<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\RateLimiting\LoginRateLimiter;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:'.LoginRateLimiter::NAME)
        ->name('login.store');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');
