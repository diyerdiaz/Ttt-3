<?php

declare(strict_types=1);

namespace App\Domain\Leads\Exceptions;

use RuntimeException;

final class UnsupportedExportFormat extends RuntimeException
{
    public static function for(string $format): self
    {
        return new self(sprintf('Unsupported export format "%s".', $format));
    }
}
