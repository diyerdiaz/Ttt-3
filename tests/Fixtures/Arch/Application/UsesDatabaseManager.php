<?php

declare(strict_types=1);

namespace Tests\Fixtures\Arch\Application;

use Illuminate\Database\DatabaseManager;

final class UsesDatabaseManager
{
    public const DEPENDENCY = DatabaseManager::class;
}
