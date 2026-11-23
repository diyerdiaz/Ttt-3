<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use Closure;

interface TransactionManager
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Closure $callback): mixed;
}
