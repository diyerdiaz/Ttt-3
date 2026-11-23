<?php

declare(strict_types=1);

use App\Domain\Activities\Contracts\ActivityRepository;
use App\Domain\Activities\Enums\ActivityType;
use App\Domain\Activities\Models\Activity;
use App\Domain\Activities\ValueObjects\ActivityData;
use App\Domain\Leads\Models\Lead;
use App\Infrastructure\Persistence\EloquentActivityRepository;
use Carbon\CarbonImmutable;
use Tests\Support\QueryCounter;

it('is bound to the Eloquent implementation', function (): void {
    expect(app(ActivityRepository::class))->toBeInstanceOf(EloquentActivityRepository::class);
});

it('persists the activity with its type and moment', function (): void {
    $lead = Lead::factory()->create();
    $moment = CarbonImmutable::parse('2026-11-20 10:30:00');

    $activity = app(ActivityRepository::class)->create($lead->id, new ActivityData('Demo', $moment, ActivityType::Meeting));

    $stored = Activity::query()->sole();
    expect($stored->id)->toBe($activity->id)
        ->and($stored->lead_id)->toBe($lead->id)
        ->and($stored->type)->toBe(ActivityType::Meeting)
        ->and($stored->description)->toBe('Demo')
        ->and($stored->occurred_at->equalTo($moment))->toBeTrue();
});

it('stores an activity without type', function (): void {
    $lead = Lead::factory()->create();

    app(ActivityRepository::class)->create($lead->id, new ActivityData('Thought about it', CarbonImmutable::now()));

    expect(Activity::query()->sole()->type)->toBeNull();
});

it('lists activities of a lead newest first', function (): void {
    $lead = Lead::factory()->create();
    Activity::factory()->for($lead)->create(['description' => 'middle', 'occurred_at' => '2026-11-10 10:00:00']);
    Activity::factory()->for($lead)->create(['description' => 'newest', 'occurred_at' => '2026-11-12 10:00:00']);
    Activity::factory()->for($lead)->create(['description' => 'oldest', 'occurred_at' => '2026-11-01 10:00:00']);

    $page = app(ActivityRepository::class)->forLead($lead->id, 10);

    expect($page->getCollection()->pluck('description')->all())->toBe(['newest', 'middle', 'oldest']);
});

it('breaks ties on the moment by the newest id', function (): void {
    $lead = Lead::factory()->create();
    $first = Activity::factory()->for($lead)->create(['occurred_at' => '2026-11-10 10:00:00']);
    $second = Activity::factory()->for($lead)->create(['occurred_at' => '2026-11-10 10:00:00']);

    $page = app(ActivityRepository::class)->forLead($lead->id, 10);

    expect($page->getCollection()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

it('does not list activities of other leads', function (): void {
    $lead = Lead::factory()->create();
    Activity::factory()->for($lead)->create(['description' => 'mine']);
    Activity::factory()->for(Lead::factory())->create(['description' => 'theirs']);

    $page = app(ActivityRepository::class)->forLead($lead->id, 10);

    expect($page->getCollection()->pluck('description')->all())->toBe(['mine']);
});

it('paginates and only loads the columns the timeline paints', function (): void {
    $lead = Lead::factory()->create();
    Activity::factory()->for($lead)->count(5)->create();

    $page = app(ActivityRepository::class)->forLead($lead->id, 2);

    expect($page->total())->toBe(5)
        ->and($page->count())->toBe(2)
        ->and(array_keys($page->first()->getAttributes()))->toBe(['id', 'type', 'description', 'occurred_at']);
});

it('runs the same number of queries whatever the number of activities', function (): void {
    $small = Lead::factory()->create();
    $large = Lead::factory()->create();
    Activity::factory()->for($small)->count(3)->create();
    Activity::factory()->for($large)->count(60)->create();
    $repository = app(ActivityRepository::class);

    $few = QueryCounter::during(fn () => $repository->forLead($small->id, 20)->items());
    $many = QueryCounter::during(fn () => $repository->forLead($large->id, 20)->items());

    expect($few)->toBe($many)->and($many)->toBe(2);
});

it('exposes the activities of a lead through the relation', function (): void {
    $lead = Lead::factory()->create();
    Activity::factory()->for($lead)->count(2)->create();

    expect($lead->activities)->toHaveCount(2)->and($lead->activities->first())->toBeInstanceOf(Activity::class);
});
