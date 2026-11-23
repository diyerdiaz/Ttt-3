<?php

declare(strict_types=1);

use Tests\Support\ArchRules;

arch()->preset()->php();
arch()->preset()->security();

arch('app uses strict types', fn () => ArchRules::assertStrictTypes());

foreach (array_keys(ArchRules::forbiddenUses('App')) as $layer) {
    arch("{$layer} does not use forbidden dependencies", fn () => ArchRules::assertNoForbiddenUses('App', $layer));
}

arch('form requests extend FormRequest', fn () => ArchRules::assertFormRequests());

foreach (ArchRules::FINAL_LAYERS as $layer) {
    arch("{$layer} classes are final", fn () => ArchRules::assertFinal($layer));
}

foreach (ArchRules::ELOQUENT_FREE_LAYERS as $layer) {
    arch("{$layer} does not hold Eloquent models", fn () => ArchRules::assertNoEloquentModels($layer));
}
