<?php

declare(strict_types=1);

namespace App\Domain\Leads\ValueObjects;

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Shared\ValueObjects\EmailAddress;

/**
 * Editable attributes of a lead. It lives in Domain because the repository contracts accept it
 * and Domain must not depend on Application.
 */
final readonly class LeadData
{
    public function __construct(
        public string $name,
        public ?EmailAddress $email = null,
        public ?PhoneNumber $phone = null,
        public ?string $company = null,
        public ?string $notes = null,
        public LeadStatus $status = LeadStatus::New,
    ) {}
}
