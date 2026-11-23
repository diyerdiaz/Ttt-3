<?php

declare(strict_types=1);

namespace App\Http\Livewire\Leads;

use App\Application\Leads\Actions\CreateLead;
use App\Application\Leads\Actions\UpdateLead;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Models\Lead;
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
        if ($lead === null) {
            Gate::authorize('create', Lead::class);

            return;
        }

        $model = $leads->findOrFail($lead);
        Gate::authorize('update', $model);

        $this->leadId = $lead;
        $this->form->fillFromLead($model);
    }

    public function save(CreateLead $create, UpdateLead $update, LeadRepository $leads): void
    {
        Gate::authorize($this->leadId === null ? 'create' : 'update', $this->leadId === null ? Lead::class : $leads->findOrFail($this->leadId));

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
