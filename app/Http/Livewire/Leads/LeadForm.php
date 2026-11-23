<?php

declare(strict_types=1);

namespace App\Http\Livewire\Leads;

use App\Application\Leads\Actions\CreateLead;
use App\Application\Leads\Actions\UpdateLead;
use App\Domain\Identity\Abilities;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Http\Livewire\Forms\LeadFormData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class LeadForm extends Component
{
    #[Locked]
    public ?int $leadId = null;

    public LeadFormData $form;

    public function mount(LeadRepository $leads, ?int $lead = null): void
    {
        Gate::authorize(Abilities::ACCESS_ADMIN);

        if ($lead !== null) {
            $this->leadId = $lead;
            $this->form->fillFromLead($leads->findOrFail($lead));
        }
    }

    public function save(CreateLead $create, UpdateLead $update): void
    {
        Gate::authorize(Abilities::ACCESS_ADMIN);

        $this->form->trimInputs();
        $this->form->validate();
        $data = $this->form->toDto();

        if ($this->leadId === null) {
            $create($data);
        } else {
            $update($this->leadId, $data);
        }

        session()->flash('status', 'Lead guardado.');

        $this->redirectRoute('leads.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.leads.lead-form')->title($this->leadId === null ? 'Nuevo lead' : 'Editar lead');
    }
}
