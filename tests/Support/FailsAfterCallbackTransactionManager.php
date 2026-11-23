<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Shared\Contracts\TransactionManager;
use Closure;
use RuntimeException;

/**
 * Runs the work inside the real transaction and then fails, so the rollback happens after the events were dispatched.
 */
final readonly class FailsAfterCallbackTransactionManager implements TransactionManager
{
    public function __construct(private TransactionManager $inner) {}

    public function run(Closure $callback): never
    {
        $this->inner->run(static function () use ($callback): never {
            $callback();

            throw new RuntimeException('Failing after the work was done.');
        });
    }
}
