<?php

declare(strict_types=1);

namespace App\Http\Livewire\Leads;

use App\Application\Leads\Actions\CreateLead;
use App\Application\Leads\Actions\UpdateLead;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Exceptions\InvalidStatusTransition;
use App\Domain\Leads\Models\Lead;
use App\Http\Livewire\Forms\LeadFormData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

final class LeadForm extends Component
{
    #[Locked]
    public ?int $leadId = null;

    #[Locked]
    public ?string $originalStatus = null;

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
        $this->originalStatus = $this->form->status;
    }

    public function updating(string $name, mixed $value): void
    {
        if (str_starts_with($name, 'form.') && (! is_string($value) || substr_count($name, '.') > 1)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function updated(string $name): void
    {
        if (str_starts_with($name, 'form.')) {
            $this->form->sanitize();
        }
    }

    public function save(CreateLead $create, UpdateLead $update, LeadRepository $leads): void
    {
        Gate::authorize($this->leadId === null ? 'create' : 'update', $this->leadId === null ? Lead::class : $leads->findOrFail($this->leadId));

        $this->form->sanitize();
        $this->form->validate();

        try {
            $this->persist($create, $update);
        } catch (InvalidStatusTransition $refused) {
            $this->addError('form.status', "No se puede mover el lead de {$refused->from->label()} a {$refused->to->label()}.");

            return;
        }

        session()->flash('status', 'Lead guardado.');

        $this->redirectRoute('leads.index', navigate: true);
    }

    private function persist(CreateLead $create, UpdateLead $update): void
    {
        if ($this->leadId === null) {
            $create($this->form->toDto());

            return;
        }

        $update($this->leadId, $this->form->toDto(withStatus: $this->form->status !== $this->originalStatus));
    }

    public function render(): View
    {
        return view('livewire.leads.lead-form')->title($this->leadId === null ? 'Nuevo lead' : 'Editar lead');
    }
}
