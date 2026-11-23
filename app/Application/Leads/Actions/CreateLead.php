<?php

declare(strict_types=1);

namespace App\Application\Leads\Actions;

use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Events\LeadCreated;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadData;
use App\Domain\Shared\Contracts\TransactionManager;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class CreateLead
{
    public function __construct(
        private LeadRepository $leads,
        private LeadBoardRepository $board,
        private TransactionManager $transactions,
        private Dispatcher $events,
    ) {}

    public function __invoke(LeadData $data): Lead
    {
        return $this->transactions->run(function () use ($data): Lead {
            $this->board->lockBoard();

            $status = $data->status ?? LeadStatus::New;
            $data = $data->withStatus($status);

            $lead = $this->leads->create($data, $this->leads->nextPositionIn($status));

            $this->events->dispatch(new LeadCreated((int) $lead->id));

            return $lead;
        });
    }
}
