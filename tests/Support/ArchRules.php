<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Single definition of every architecture rule. The real suite applies them to the "App"
 * root and the canaries apply the very same methods to offending fixtures.
 *
 * Rules take ONE subject on purpose: with an array of subjects, `not->toUse()` only fails
 * when EVERY subject violates it (Pest builds a "toUse" per dependency and inverts it), so
 * a violation in a single layer goes unnoticed. Dependency arrays are safe.
 */
final class ArchRules
{
    /** Only these directories may exist directly under app/. */
    public const APP_DIRECTORIES = ['Domain', 'Application', 'Infrastructure', 'Http', 'Providers'];

    public const ELOQUENT_FREE_LAYERS = ['Application', 'Http', 'Infrastructure'];

    /** Entry points to the database that bypass the repositories. */
    public const DATABASE_ACCESS = [
        'Illuminate\Support\Facades\DB',
        'Illuminate\Database\DatabaseManager',
        'Illuminate\Database\ConnectionInterface',
        'Illuminate\Database\Connection',
    ];

    public const FINAL_LAYERS = ['Application', 'Infrastructure'];

    /**
     * @return array<string, list<string>> layer => namespaces it must not use
     */
    public static function forbiddenUses(string $root): array
    {
        return [
            'Domain' => [
                $root.'\Application',
                $root.'\Http',
                $root.'\Infrastructure',
                'Illuminate\Http',
                'Livewire',
            ],
            'Application' => [
                $root.'\Http',
                $root.'\Infrastructure',
                'Illuminate\Http',
                'Livewire',
                ...self::DATABASE_ACCESS,
            ],
            'Http' => [
                $root.'\Infrastructure',
                ...self::DATABASE_ACCESS,
            ],
        ];
    }

    public static function assertNoForbiddenUses(string $root, string $layer, ?string $subject = null): void
    {
        self::verify(expect($subject ?? $root.'\\'.$layer)->not->toUse(self::forbiddenUses($root)[$layer]));
    }

    public static function assertFinal(string $layer, ?string $subject = null): void
    {
        self::verify(expect($subject ?? 'App\\'.$layer)->classes()->toBeFinal());
    }

    public static function assertNoEloquentModels(string $layer, ?string $subject = null): void
    {
        self::verify(expect($subject ?? 'App\\'.$layer)->not->toExtend(Model::class));
    }

    public static function assertFormRequests(string $subject = 'App\Http\Requests'): void
    {
        self::verify(expect($subject)->toExtend(FormRequest::class));
    }

    public static function assertStrictTypes(string $subject = 'App'): void
    {
        self::verify(expect($subject)->toUseStrictTypes());
    }

    /**
     * @return list<string> entries of $appPath that are not an allowed layer directory
     */
    public static function unexpectedAppEntries(string $appPath): array
    {
        $entries = array_diff(scandir($appPath) ?: [], ['.', '..']);

        return array_values(array_filter(
            $entries,
            static fn (string $entry): bool => ! is_dir($appPath.'/'.$entry)
                || ! in_array($entry, self::APP_DIRECTORIES, true),
        ));
    }

    /**
     * Pest evaluates arch expectations lazily, in the destructor; forcing it makes them
     * fail inside the calling closure.
     */
    private static function verify(object $expectation): void
    {
        $expectation->__destruct();
    }
}
