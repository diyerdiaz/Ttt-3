<?php

declare(strict_types=1);

use App\Http\Livewire\Leads\LeadForm;
use App\Http\Livewire\Leads\LeadIndex;
use App\Http\Livewire\Leads\LeadShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('leads')->group(function (): void {
    Route::get('/', LeadIndex::class)->name('leads.index');
    Route::get('/create', LeadForm::class)->name('leads.create');
    Route::get('/{lead}/edit', LeadForm::class)->where('lead', '[0-9]{1,18}')->name('leads.edit');
    Route::get('/{lead}', LeadShow::class)->where('lead', '[0-9]{1,18}')->name('leads.show');
});
