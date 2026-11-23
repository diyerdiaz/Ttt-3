<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\AssertionFailedError;
use Tests\Fixtures\Arch\Http\Controllers\BaseController;
use Tests\Support\ArchRules;

/*
 * Canaries: every architecture rule is applied to a deliberately offending fixture under
 * tests/Fixtures/Arch and MUST fail. Without them the real rules pass vacuously while the
 * layers are empty (or silently skip a case) and nobody notices. The dataset is written by
 * hand on purpose: deriving it from the rules would drop a canary together with its rule.
 */

dataset('forbidden uses', [
    'domain -> application' => ['Domain', 'UsesApplication', 'Tests\\Fixtures\\Arch\\Application'],
    'domain -> http' => ['Domain', 'UsesHttp', 'Tests\\Fixtures\\Arch\\Http'],
    'domain -> infrastructure' => ['Domain', 'UsesInfrastructure', 'Tests\\Fixtures\\Arch\\Infrastructure'],
    'domain -> illuminate http' => ['Domain', 'UsesIlluminateHttp', 'Illuminate\\Http'],
    'domain -> livewire (last dependency)' => ['Domain', 'UsesLivewire', 'Livewire'],
    'application -> http' => ['Application', 'UsesHttp', 'Tests\\Fixtures\\Arch\\Http'],
    'application -> infrastructure' => ['Application', 'UsesInfrastructure', 'Tests\\Fixtures\\Arch\\Infrastructure'],
    'application -> illuminate http' => ['Application', 'UsesIlluminateHttp', 'Illuminate\\Http'],
    'application -> livewire' => ['Application', 'UsesLivewire', 'Livewire'],
    'application -> DB facade' => ['Application', 'UsesDbFacade', 'Illuminate\\Support\\Facades\\DB'],
    'http -> infrastructure' => ['Http', 'UsesInfrastructure', 'Tests\\Fixtures\\Arch\\Infrastructure'],
    'http -> DB facade' => ['Http', 'UsesDbFacade', 'Illuminate\\Support\\Facades\\DB'],
    'application -> DatabaseManager' => ['Application', 'UsesDatabaseManager', 'Illuminate\\Database\\DatabaseManager'],
    'application -> ConnectionInterface' => ['Application', 'UsesConnectionInterface', 'Illuminate\\Database\\ConnectionInterface'],
    'application -> Connection' => ['Application', 'UsesConnection', 'Illuminate\\Database\\Connection'],
    'http -> DatabaseManager' => ['Http', 'UsesDatabaseManager', 'Illuminate\\Database\\DatabaseManager'],
    'http -> ConnectionInterface' => ['Http', 'UsesConnectionInterface', 'Illuminate\\Database\\ConnectionInterface'],
    'http -> Connection' => ['Http', 'UsesConnection', 'Illuminate\\Database\\Connection'],
]);

it('detects a forbidden dependency', function (string $layer, string $class, string $dependency): void {
    expect(fn () => ArchRules::assertNoForbiddenUses(ArchRules::FIXTURES, $layer, ArchRules::fixture($layer, $class)))
        ->toThrow(AssertionFailedError::class, "not to use '".substr($dependency, 0, 25)); // Pest truncates long names in the middle
})->with('forbidden uses');

it('accepts a class without forbidden dependencies', function (): void {
    expect(fn () => ArchRules::assertNoForbiddenUses(ArchRules::FIXTURES, 'Domain', ArchRules::fixture('Domain', 'Clean')))
        ->not->toThrow(AssertionFailedError::class);
});

it('detects a violation in a namespace that mixes clean and offending classes', function (): void {
    expect(fn () => ArchRules::assertNoForbiddenUses(ArchRules::FIXTURES, 'Domain', ArchRules::fixture('Mixed', '')))
        ->toThrow(AssertionFailedError::class, "not to use 'Illuminate\\Http'");
});

it('detects a non-final class', function (string $layer): void {
    expect(fn () => ArchRules::assertFinal($layer, ArchRules::fixture($layer, 'NotFinal')))
        ->toThrow(AssertionFailedError::class, 'to be final');
})->with(['Application', 'Infrastructure']);

it('detects an Eloquent model outside the domain', function (string $layer): void {
    expect(fn () => ArchRules::assertNoEloquentModels($layer, ArchRules::fixture($layer, 'ExtendsModel')))
        ->toThrow(AssertionFailedError::class, 'not to extend');
})->with(['Application', 'Http', 'Infrastructure']);

it('detects a request that is not a FormRequest', function (): void {
    expect(fn () => ArchRules::assertFormRequests(ArchRules::fixture('Http\Requests', 'NotAFormRequest')))
        ->toThrow(AssertionFailedError::class, 'to extend');
});

it('accepts a FormRequest', function (): void {
    expect(fn () => ArchRules::assertFormRequests(ArchRules::fixture('Http\Requests', 'ValidRequest')))
        ->not->toThrow(AssertionFailedError::class);
});

dataset('thin controller violations', [
    'query builder' => ['UsesQueryBuilder', 'Illuminate\\Database'],
    'domain model' => ['UsesDomainModel', 'Tests\\Fixtures\\Arch\\Domain\\Identity\\Models'],
]);

it('detects a controller that touches Eloquent or a domain model', function (string $class, string $dependency): void {
    expect(fn () => ArchRules::assertThinControllers(ArchRules::FIXTURES, ArchRules::fixture('Http\Controllers', $class)))
        ->toThrow(AssertionFailedError::class, "not to use '".substr($dependency, 0, 25));
})->with('thin controller violations');

it('accepts a thin controller', function (): void {
    expect(fn () => ArchRules::assertThinControllers(ArchRules::FIXTURES, ArchRules::fixture('Http\Controllers', 'ThinController')))
        ->not->toThrow(AssertionFailedError::class);
});

it('detects non-final controllers, middleware and requests', function (string $class): void {
    expect(fn () => ArchRules::assertFinalClasses(ArchRules::fixture('Http', $class)))
        ->toThrow(AssertionFailedError::class, 'to be final');
})->with(['Controllers\NotFinalController', 'Middleware\NotFinalMiddleware', 'Requests\NotFinalRequest']);

it('detects a controller that does not extend the base controller', function (): void {
    expect(fn () => ArchRules::assertControllersExtendBase(ArchRules::fixture('Http\Controllers', 'NotFinalController'), BaseController::class))
        ->not->toThrow(AssertionFailedError::class);

    expect(fn () => ArchRules::assertControllersExtendBase(ArchRules::fixture('Http\Controllers', 'OrphanController'), BaseController::class))
        ->toThrow(AssertionFailedError::class, 'to extend');
});

it('detects a file without strict types', function (): void {
    expect(fn () => ArchRules::assertStrictTypes(ArchRules::fixture('Violations', 'MissingStrictTypes')))
        ->toThrow(AssertionFailedError::class, 'strict types');
});

it('detects a root namespace outside the layer allowlist', function (): void {
    $app = sys_get_temp_dir().'/arch-canary-'.bin2hex(random_bytes(4));

    foreach (['Domain', 'Http', 'Services', 'Models', '.hidden'] as $directory) {
        mkdir($app.'/'.$directory, 0777, true);
    }
    touch($app.'/Stray.php');
    touch($app.'/.gitkeep');

    try {
        expect(ArchRules::unexpectedAppEntries($app))->toBe(['Models', 'Services', 'Stray.php']);
    } finally {
        (new Filesystem)->deleteDirectory($app);
    }
});
