<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Shared\Contracts\TransactionManager;
use Closure;

/**
 * Runs the closure immediately and writes begin/end markers into the store's call log.
 */
final readonly class InlineTransactionManager implements TransactionManager
{
    public function __construct(private InMemoryLeadStore $store) {}

    public function run(Closure $callback): mixed
    {
        $this->store->calls[] = 'begin';
        $result = $callback();
        $this->store->calls[] = 'end';

        return $result;
    }
}
