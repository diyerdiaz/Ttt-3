<?php

declare(strict_types=1);

namespace Tests\Fixtures\Arch\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;

final class UsesQueryBuilder extends BaseController
{
    public const DEPENDENCY = Builder::class;
}
