<?php

declare(strict_types=1);

use App\Application\Activities\Actions\LogActivity;
use App\Domain\Activities\Enums\ActivityType;
use App\Domain\Activities\Events\ActivityLogged;
use App\Domain\Activities\ValueObjects\ActivityData;
use Carbon\CarbonImmutable;
use Tests\Support\InMemoryActivityRepository;
use Tests\Support\LeadActions;

it('stores the activity for the given lead', function (): void {
    $repository = new InMemoryActivityRepository;
    $moment = CarbonImmutable::parse('2026-11-20 09:00:00');
    $log = [];

    $activity = (new LogActivity($repository, LeadActions::recordingDispatcher([ActivityLogged::class], $log)))(7, new ActivityData('Intro call', $moment, ActivityType::Call));

    expect($activity->lead_id)->toBe(7)
        ->and($activity->description)->toBe('Intro call')
        ->and($activity->type)->toBe(ActivityType::Call)
        ->and($repository->activities)->toHaveCount(1);
});

it('dispatches ActivityLogged with the new ids', function (): void {
    $log = [];
    $events = LeadActions::recordingDispatcher([ActivityLogged::class], $log);

    $activity = (new LogActivity(new InMemoryActivityRepository, $events))(7, new ActivityData('Note', CarbonImmutable::now()));

    expect($log)->toHaveCount(1)
        ->and($log[0])->toBeInstanceOf(ActivityLogged::class)
        ->and($log[0]->activityId)->toBe($activity->id)
        ->and($log[0]->leadId)->toBe(7);
});
