<?php

declare(strict_types=1);

namespace App\Http\Livewire\Leads;

use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Models\Lead;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Lead $lead
 */
final class LeadShow extends Component
{
    #[Locked]
    public int $leadId;

    public function mount(int $lead): void
    {
        $this->leadId = $lead;
    }

    #[Computed]
    public function lead(): Lead
    {
        $lead = app(LeadRepository::class)->findOrFail($this->leadId);
        Gate::authorize('view', $lead);

        return $lead;
    }

    public function render(): View
    {
        return view('livewire.leads.lead-show')->title($this->lead->name);
    }
}
