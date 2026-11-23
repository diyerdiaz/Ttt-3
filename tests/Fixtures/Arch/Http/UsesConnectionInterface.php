<?php

declare(strict_types=1);

namespace Tests\Fixtures\Arch\Http;

use Illuminate\Database\ConnectionInterface;

final class UsesConnectionInterface
{
    public const DEPENDENCY = ConnectionInterface::class;
}
