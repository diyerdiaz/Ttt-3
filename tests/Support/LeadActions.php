<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Leads\Actions\CreateLead;
use App\Application\Leads\Actions\UpdateLead;
use Illuminate\Events\Dispatcher;

/**
 * Builds the lead Actions over the in-memory fakes for unit tests.
 */
final class LeadActions
{
    public static function create(InMemoryLeadStore $store, ?Dispatcher $events = null): CreateLead
    {
        return new CreateLead($store, $store, new InlineTransactionManager($store), $events ?? new Dispatcher);
    }

    public static function update(InMemoryLeadStore $store, ?Dispatcher $events = null): UpdateLead
    {
        return new UpdateLead($store, $store, new InlineTransactionManager($store), $events ?? new Dispatcher);
    }

    /**
     * @param  list<class-string>  $eventClasses
     * @param  list<object>  $log  filled by reference with every dispatched event, in order
     */
    public static function recordingDispatcher(array $eventClasses, array &$log): Dispatcher
    {
        $events = new Dispatcher;
        $events->listen($eventClasses, static function (string $name, array $payload) use (&$log): void {
            $log[] = $payload[0];
        });

        return $events;
    }
}
