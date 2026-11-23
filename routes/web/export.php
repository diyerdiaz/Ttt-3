<?php

declare(strict_types=1);

use App\Http\Controllers\ExportLeadsController;
use Illuminate\Support\Facades\Route;

// Loaded before routes/web/leads.php (glob order), so it also wins over any future /leads/{lead}.
Route::get('/leads/export', ExportLeadsController::class)
    ->middleware(['auth', 'admin', 'throttle:10,1'])
    ->name('leads.export');
