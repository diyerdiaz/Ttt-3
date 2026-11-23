<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Vite;

it('renders pages without compiled assets', function (): void {
    Vite::useBuildDirectory('missing-build-directory');

    $this->get('/login')->assertOk();
});
