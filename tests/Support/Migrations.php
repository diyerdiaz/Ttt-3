<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Migrations\Migration;

final class Migrations
{
    public static function load(string $name): Migration
    {
        return require database_path('migrations/'.$name.'.php');
    }
}
