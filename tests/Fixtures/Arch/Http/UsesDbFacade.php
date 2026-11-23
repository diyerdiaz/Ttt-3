<?php

declare(strict_types=1);

namespace Tests\Fixtures\Arch\Http;

use Illuminate\Support\Facades\DB;

final class UsesDbFacade
{
    public const DEPENDENCY = DB::class;
}
