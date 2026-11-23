<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Activities\Contracts\ActivityRepository;
use App\Domain\Activities\Models\Activity;
use App\Domain\Activities\Policies\ActivityPolicy;
use App\Domain\Identity\Contracts\UserRepository;
use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Contracts\LeadExporterFactory;
use App\Domain\Leads\Contracts\LeadExportSource;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\Policies\LeadPolicy;
use App\Domain\Shared\Contracts\TransactionManager;
use App\Infrastructure\Export\ConfiguredLeadExporterFactory;
use App\Infrastructure\Persistence\DatabaseTransactionManager;
use App\Infrastructure\Persistence\EloquentActivityRepository;
use App\Infrastructure\Persistence\EloquentLeadExportSource;
use App\Infrastructure\Persistence\EloquentLeadRepository;
use App\Infrastructure\Persistence\EloquentUserRepository;
use Illuminate\Support\Facades\Gate;
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
        ActivityRepository::class => EloquentActivityRepository::class,
        LeadExportSource::class => EloquentLeadExportSource::class,
        LeadExporterFactory::class => ConfiguredLeadExporterFactory::class,
        TransactionManager::class => DatabaseTransactionManager::class,
    ];

    public function boot(): void
    {
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
