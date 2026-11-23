<?php

declare(strict_types=1);

namespace Tests\Fixtures\Arch\Http\Controllers;

use Tests\Fixtures\Arch\Domain\Identity\Models\Account;

final class UsesDomainModel extends BaseController
{
    public const DEPENDENCY = Account::class;
}
