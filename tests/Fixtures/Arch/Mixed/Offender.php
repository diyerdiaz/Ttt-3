<?php

declare(strict_types=1);

namespace Tests\Fixtures\Arch\Mixed;

use Illuminate\Http\Request;

final class Offender
{
    public const DEPENDENCY = Request::class;
}
