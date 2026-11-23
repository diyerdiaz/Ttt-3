<?php

declare(strict_types=1);

namespace App\Application\Leads\Actions;

use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Events\LeadStatusChanged;
use App\Domain\Leads\Events\LeadUpdated;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadData;
use App\Domain\Shared\Contracts\TransactionManager;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class UpdateLead
{
    public function __construct(
        private LeadRepository $leads,
        private LeadBoardRepository $board,
        private TransactionManager $transactions,
        private Dispatcher $events,
    ) {}

    public function __invoke(int $id, LeadData $data): Lead
    {
        return $this->transactions->run(function () use ($id, $data): Lead {
            $this->board->lockBoard();

            $current = $this->leads->findOrFail($id);
            $from = $current->status;
            $statusChanged = $from !== $data->status;

            $lead = $this->leads->update(
                $current,
                $data,
                $statusChanged ? $this->leads->nextPositionIn($data->status) : null,
            );

            $this->events->dispatch(new LeadUpdated($id));

            if ($statusChanged) {
                $this->events->dispatch(new LeadStatusChanged($id, $from, $data->status));
            }

            return $lead;
        });
    }
}
