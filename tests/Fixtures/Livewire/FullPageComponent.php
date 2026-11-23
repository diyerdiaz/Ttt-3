<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use Livewire\Component;

final class FullPageComponent extends Component
{
    public function render(): string
    {
        return '<div>fixture-full-page</div>';
    }
}
