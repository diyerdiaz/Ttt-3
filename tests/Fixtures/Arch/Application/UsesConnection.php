<?php

declare(strict_types=1);

namespace Tests\Fixtures\Arch\Application;

use Illuminate\Database\Connection;

final class UsesConnection
{
    public const DEPENDENCY = Connection::class;
}
