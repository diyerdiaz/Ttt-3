<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Vite;

it('the test suite does not depend on compiled assets', function (): void {
    Vite::useBuildDirectory('missing-build-directory');

    $this->get('/login')->assertOk();
});
