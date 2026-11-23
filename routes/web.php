<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

foreach (glob(__DIR__.'/web/*.php') ?: [] as $routeFile) {
    Route::middleware('web')->group($routeFile);
}
