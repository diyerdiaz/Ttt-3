<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Identity\Contracts\UserRepository;
use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Shared\Contracts\TransactionManager;
use App\Infrastructure\Persistence\DatabaseTransactionManager;
use App\Infrastructure\Persistence\EloquentLeadRepository;
use App\Infrastructure\Persistence\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        LeadRepository::class => EloquentLeadRepository::class,
        LeadBoardRepository::class => EloquentLeadRepository::class,
        TransactionManager::class => DatabaseTransactionManager::class,
    ];
}
