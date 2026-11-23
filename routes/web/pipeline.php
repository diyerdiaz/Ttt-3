<?php

declare(strict_types=1);

use App\Http\Livewire\Pipeline\PipelineBoard;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->get('/pipeline', PipelineBoard::class)->name('pipeline');
