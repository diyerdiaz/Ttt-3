<?php

declare(strict_types=1);

namespace App\Application\Leads\Actions;

use App\Application\Leads\DTOs\MoveLeadData;
use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Events\LeadStatusChanged;
use App\Domain\Leads\Exceptions\InvalidStatusTransition;
use App\Domain\Leads\Exceptions\StaleBoardState;
use App\Domain\Shared\Contracts\TransactionManager;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class MoveLead
{
    public function __construct(
        private LeadBoardRepository $board,
        private TransactionManager $transactions,
        private Dispatcher $events,
    ) {}

    public function __invoke(MoveLeadData $data): void
    {
        $this->transactions->run(function () use ($data): void {
            $this->board->lockBoard();

            $lead = $this->board->findForUpdate($data->leadId);
            $from = $lead->status;
            $statusChanged = $from !== $data->to;

            if ($statusChanged && ! $from->canTransitionTo($data->to)) {
                throw InvalidStatusTransition::between($from, $data->to);
            }

            $this->assertOrderMatchesColumn($data);

            $this->board->placeColumn($data->to, $data->orderedIds);

            if ($statusChanged) {
                $this->events->dispatch(new LeadStatusChanged($data->leadId, $from, $data->to));
            }
        });
    }

    private function assertOrderMatchesColumn(MoveLeadData $data): void
    {
        $expected = $this->board->idsInColumn($data->to);

        if (! in_array($data->leadId, $expected, true)) {
            $expected[] = $data->leadId;
        }

        $given = $data->orderedIds;
        sort($expected);
        sort($given);

        if ($expected !== $given) {
            throw StaleBoardState::forColumn($data->to);
        }
    }
}
