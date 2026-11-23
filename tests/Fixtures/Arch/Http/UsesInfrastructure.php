<?php

declare(strict_types=1);

namespace Tests\Fixtures\Arch\Http;

use Tests\Fixtures\Arch\Infrastructure\Marker;

final class UsesInfrastructure
{
    public const DEPENDENCY = Marker::class;
}
