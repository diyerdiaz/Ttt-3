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
        return new CreateLead($store, $store, new InlineTransactionManager($store), $events ?? self::dispatcher());
    }

    public static function update(InMemoryLeadStore $store, ?Dispatcher $events = null): UpdateLead
    {
        return new UpdateLead($store, $store, new InlineTransactionManager($store), $events ?? self::dispatcher());
    }

    /**
     * @param  list<class-string>  $eventClasses
     * @param  list<object>  $log  filled by reference with every dispatched event, in order
     */
    public static function recordingDispatcher(array $eventClasses, array &$log): Dispatcher
    {
        $events = self::dispatcher();
        $events->listen($eventClasses, static function (object $event) use (&$log): void {
            $log[] = $event;
        });

        return $events;
    }

    /**
     * Outside a Laravel app there is no transaction manager, so events are dispatched immediately.
     */
    private static function dispatcher(): Dispatcher
    {
        return (new Dispatcher)->setTransactionManagerResolver(static fn (): null => null);
    }
}
