<?php

declare(strict_types=1);

namespace Tests\Support;

final class ConfigEnv
{
    /**
     * Evaluates a config file under temporary environment values. The Dotenv repository is
     * immutable, so the superglobals it reads first are swapped instead.
     *
     * @param  array<string, string|null>  $env
     * @return array<string, mixed>
     */
    public static function evaluate(string $file, array $env): array
    {
        $previous = [];

        foreach ($env as $name => $value) {
            $previous[$name] = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null, getenv($name)];
            unset($_ENV[$name], $_SERVER[$name]);
            putenv($name);

            if ($value !== null) {
                $_ENV[$name] = $_SERVER[$name] = $value;
                putenv($name.'='.$value);
            }
        }

        try {
            return require config_path($file.'.php');
        } finally {
            foreach ($previous as $name => [$envValue, $serverValue, $processValue]) {
                unset($_ENV[$name], $_SERVER[$name]);
                putenv($processValue === false ? $name : $name.'='.$processValue);

                if ($envValue !== null) {
                    $_ENV[$name] = $envValue;
                }

                if ($serverValue !== null) {
                    $_SERVER[$name] = $serverValue;
                }
            }
        }
    }
}
