<?php

declare(strict_types=1);

namespace App\Domain\Leads\Contracts;

use App\Domain\Leads\Models\Lead;

/**
 * Strategy: one implementation per export format.
 */
interface LeadExporter
{
    public function contentType(): string;

    public function extension(): string;

    /**
     * Writes the leads one by one to an already open stream; never buffers them.
     *
     * @param  iterable<Lead>  $leads
     * @param  resource  $out
     */
    public function write(iterable $leads, $out): void;
}
