<?php

declare(strict_types=1);

use App\Http\Controllers\Controller;
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

arch('controllers do not touch Eloquent or domain models', fn () => ArchRules::assertThinControllers('App'));

arch('controllers extend the base controller', fn () => ArchRules::assertControllersExtendBase());

arch('controllers are final', fn () => ArchRules::assertFinalClasses('App\Http\Controllers', [Controller::class]));

arch('middleware are final', fn () => ArchRules::assertFinalClasses('App\Http\Middleware'));

arch('form requests are final', fn () => ArchRules::assertFinalClasses('App\Http\Requests'));

it('keeps app/ restricted to the allowed layer directories', function (): void {
    expect(ArchRules::unexpectedAppEntries(dirname(__DIR__, 2).'/app'))->toBe([]);
});
