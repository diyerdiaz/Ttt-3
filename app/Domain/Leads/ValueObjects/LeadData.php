<?php

declare(strict_types=1);

namespace App\Domain\Leads\ValueObjects;

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Shared\ValueObjects\EmailAddress;
use InvalidArgumentException;

/**
 * Editable attributes of a lead. It lives in Domain because the repository contracts accept it
 * and Domain must not depend on Application. A null status means "not specified": a new lead
 * starts as New and an existing one keeps the status it has.
 */
final readonly class LeadData
{
    public const int NAME_MAX_LENGTH = 150;

    public function __construct(
        public string $name,
        public ?EmailAddress $email = null,
        public ?PhoneNumber $phone = null,
        public ?string $company = null,
        public ?string $notes = null,
        public ?LeadStatus $status = null,
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('The lead name must not be blank.');
        }

        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new InvalidArgumentException('The lead name must not exceed '.self::NAME_MAX_LENGTH.' characters.');
        }
    }

    public function withStatus(LeadStatus $status): self
    {
        return new self($this->name, $this->email, $this->phone, $this->company, $this->notes, $status);
    }
}
