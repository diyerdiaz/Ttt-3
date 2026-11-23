<?php

declare(strict_types=1);

namespace Tests\Fixtures\Arch\Application;

use Illuminate\Support\Facades\DB;

final class UsesDbFacade
{
    public const DEPENDENCY = DB::class;
}
