<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

use App\Domain\Shared\ValueObjects\EmailAddress;
use SensitiveParameter;

final readonly class NewUserData
{
    public function __construct(
        public string $name,
        public EmailAddress $email,
        #[SensitiveParameter]
        public string $plainPassword,
    ) {}
}
