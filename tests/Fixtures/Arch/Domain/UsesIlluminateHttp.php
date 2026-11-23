<?php

declare(strict_types=1);

namespace Tests\Fixtures\Arch\Domain;

use Illuminate\Http\Request;

final class UsesIlluminateHttp
{
    public const DEPENDENCY = Request::class;
}
