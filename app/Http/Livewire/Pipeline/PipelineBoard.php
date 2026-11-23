<?php

declare(strict_types=1);

namespace App\Http\Livewire\Pipeline;

use App\Application\Leads\Actions\MoveLead;
use App\Application\Leads\DTOs\MoveLeadData;
use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Exceptions\InvalidStatusTransition;
use App\Domain\Leads\Exceptions\StaleBoardState;
use App\Domain\Leads\Models\Lead;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

final class PipelineBoard extends Component
{
    private const int MAX_COLUMN_SIZE = 1000;

    public function mount(): void
    {
        Gate::authorize('viewAny', Lead::class);
    }

    /**
     * The component has no client-writable state: every update coming from the browser is tampering.
     */
    public function updating(string $name, mixed $value): void
    {
        abort(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return array<string, list<Lead>>
     */
    #[Computed]
    public function columns(): array
    {
        return app(LeadBoardRepository::class)->columns();
    }

    /**
     * Parameters are untyped on purpose: a manipulated payload must answer 422, not a PHP TypeError (500).
     *
     * @param  mixed  $orderedIds  ids of the destination column in the dropped order, including the moved lead
     */
    public function moveLead(mixed $leadId, mixed $toStatus, mixed $orderedIds, LeadRepository $leads, MoveLead $move): void
    {
        $data = $this->validatedMove($leadId, $toStatus, $orderedIds);

        Gate::authorize('move', $leads->findOrFail($data->leadId));

        try {
            $move($data);
        } catch (StaleBoardState) {
            $this->addError('move', 'El tablero cambió en otra pestaña. Se recargó con los datos actuales; vuelve a intentarlo.');
        } catch (InvalidStatusTransition $refused) {
            $this->addError('move', "No se puede mover el lead de {$refused->from->label()} a {$refused->to->label()}.");
        }

        unset($this->columns);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Lead::class);

        return view('livewire.pipeline.pipeline-board', ['statuses' => LeadStatus::boardOrder()])->title('Pipeline');
    }

    private function validatedMove(mixed $leadId, mixed $toStatus, mixed $orderedIds): MoveLeadData
    {
        $to = is_string($toStatus) ? LeadStatus::tryFrom($toStatus) : null;

        if (! is_int($leadId) || $leadId < 1 || $to === null || ! $this->isValidOrder($orderedIds, $leadId)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var list<int> $orderedIds */
        return new MoveLeadData($leadId, $to, $orderedIds);
    }

    private function isValidOrder(mixed $orderedIds, int $leadId): bool
    {
        if (! is_array($orderedIds) || ! array_is_list($orderedIds) || count($orderedIds) > self::MAX_COLUMN_SIZE) {
            return false;
        }

        foreach ($orderedIds as $id) {
            if (! is_int($id) || $id < 1) {
                return false;
            }
        }

        return count(array_unique($orderedIds)) === count($orderedIds) && in_array($leadId, $orderedIds, true);
    }
}
