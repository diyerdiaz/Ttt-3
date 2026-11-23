<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Tests\Support\ArchRules;

/*
 * Canaries: every architecture rule is applied to a deliberately offending fixture under
 * tests/Fixtures/Arch and MUST fail. Without them the real rules pass vacuously while the
 * layers are empty (or silently skip a case) and nobody notices. The dataset is written by
 * hand on purpose: deriving it from the rules would drop a canary together with its rule.
 */

const ARCH_FIXTURES = 'Tests\Fixtures\Arch';

function archFixture(string $layer, string $class): string
{
    return ARCH_FIXTURES.'\\'.$layer.'\\'.$class;
}

function archDependency(string $dependency): string
{
    return in_array($dependency, ['Application', 'Http', 'Infrastructure'], true)
        ? ARCH_FIXTURES.'\\'.$dependency
        : $dependency;
}

dataset('forbidden uses', [
    'domain -> application' => ['Domain', 'UsesApplication', 'Application'],
    'domain -> http' => ['Domain', 'UsesHttp', 'Http'],
    'domain -> infrastructure' => ['Domain', 'UsesInfrastructure', 'Infrastructure'],
    'domain -> illuminate http' => ['Domain', 'UsesIlluminateHttp', 'Illuminate\Http'],
    'domain -> livewire (last dependency)' => ['Domain', 'UsesLivewire', 'Livewire'],
    'application -> http' => ['Application', 'UsesHttp', 'Http'],
    'application -> infrastructure' => ['Application', 'UsesInfrastructure', 'Infrastructure'],
    'application -> illuminate http' => ['Application', 'UsesIlluminateHttp', 'Illuminate\Http'],
    'application -> livewire' => ['Application', 'UsesLivewire', 'Livewire'],
    'application -> DB facade' => ['Application', 'UsesDbFacade', 'Illuminate\Support\Facades\DB'],
    'http -> infrastructure' => ['Http', 'UsesInfrastructure', 'Infrastructure'],
    'http -> DB facade' => ['Http', 'UsesDbFacade', 'Illuminate\Support\Facades\DB'],
]);

it('detects a forbidden dependency', function (string $layer, string $class, string $dependency): void {
    expect(fn () => ArchRules::assertNoForbiddenUses(ARCH_FIXTURES, $layer, archFixture($layer, $class)))
        ->toThrow(AssertionFailedError::class, archDependency($dependency));
})->with('forbidden uses');

it('accepts a class without forbidden dependencies', function (): void {
    ArchRules::assertNoForbiddenUses(ARCH_FIXTURES, 'Domain', archFixture('Domain', 'Clean'));

    expect(true)->toBeTrue();
});

it('detects a non-final class', function (string $layer): void {
    expect(fn () => ArchRules::assertFinal($layer, archFixture($layer, 'NotFinal')))
        ->toThrow(AssertionFailedError::class, 'to be final');
})->with(['Application', 'Infrastructure']);

it('detects an Eloquent model outside the domain', function (string $layer): void {
    expect(fn () => ArchRules::assertNoEloquentModels($layer, archFixture($layer, 'ExtendsModel')))
        ->toThrow(AssertionFailedError::class, 'not to extend');
})->with(['Application', 'Http', 'Infrastructure']);

it('detects a request that is not a FormRequest', function (): void {
    expect(fn () => ArchRules::assertFormRequests(archFixture('Http\Requests', 'NotAFormRequest')))
        ->toThrow(AssertionFailedError::class, 'to extend');
});

it('accepts a FormRequest', function (): void {
    ArchRules::assertFormRequests(archFixture('Http\Requests', 'ValidRequest'));

    expect(true)->toBeTrue();
});

it('detects a file without strict types', function (): void {
    expect(fn () => ArchRules::assertStrictTypes(archFixture('Violations', 'MissingStrictTypes')))
        ->toThrow(AssertionFailedError::class, 'strict types');
});

it('detects a root namespace outside the layer allowlist', function (): void {
    $app = sys_get_temp_dir().'/arch-canary-'.bin2hex(random_bytes(4));

    foreach (['Domain', 'Http', 'Services', 'Models'] as $directory) {
        mkdir($app.'/'.$directory, 0777, true);
    }
    touch($app.'/Stray.php');

    try {
        expect(ArchRules::unexpectedAppEntries($app))->toBe(['Models', 'Services', 'Stray.php']);
    } finally {
        array_map(rmdir(...), glob($app.'/*', GLOB_ONLYDIR) ?: []);
        unlink($app.'/Stray.php');
        rmdir($app);
    }
});
