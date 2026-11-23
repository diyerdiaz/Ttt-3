<?php

declare(strict_types=1);

use App\Domain\Activities\Enums\ActivityType;
use App\Domain\Activities\Events\ActivityLogged;
use App\Domain\Activities\Models\Activity;
use App\Domain\Leads\Models\Lead;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Tests\Support\Accounts;
use Tests\Support\TimelineComponent;

beforeEach(function (): void {
    $this->actingAs(Accounts::admin());
    $this->travelTo(CarbonImmutable::parse('2026-11-23 12:00:00'));
    $this->lead = Lead::factory()->create();
});

it('logs an activity from the lead page', function (): void {
    $logged = [];
    Event::listen(ActivityLogged::class, static function (ActivityLogged $event) use (&$logged): void {
        $logged[] = $event;
    });

    TimelineComponent::open($this->lead)
        ->set('type', 'meeting')
        ->set('description', 'Demo of the product')
        ->set('occurredAt', '2026-11-20T10:30')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Demo of the product')
        ->assertSee('Reunión')
        ->assertSee('20/11/2026 10:30');

    $activity = Activity::query()->sole();

    expect($activity->lead_id)->toBe($this->lead->id)
        ->and($activity->type)->toBe(ActivityType::Meeting)
        ->and($activity->description)->toBe('Demo of the product')
        ->and($activity->occurred_at->format('Y-m-d H:i:s'))->toBe('2026-11-20 10:30:00')
        ->and($logged)->toHaveCount(1)
        ->and($logged[0]->activityId)->toBe($activity->id)
        ->and($logged[0]->leadId)->toBe($this->lead->id);
});

it('defaults the moment to now and clears the form after saving', function (): void {
    TimelineComponent::open($this->lead)
        ->assertSet('occurredAt', '2026-11-23T12:00')
        ->assertSet('type', '')
        ->set('description', 'Quick note')
        ->set('occurredAt', '2026-11-01T08:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('description', '')
        ->assertSet('type', '')
        ->assertSet('occurredAt', '2026-11-23T12:00');
});

it('accepts an activity without type', function (): void {
    TimelineComponent::open($this->lead)->set('description', 'No type')->call('save')->assertHasNoErrors();

    expect(Activity::query()->sole()->type)->toBeNull();
});

it('requires a description', function (string $description): void {
    TimelineComponent::open($this->lead)->set('description', $description)->call('save')->assertHasErrors(['description' => 'required']);

    expect(Activity::query()->count())->toBe(0);
})->with([
    'empty' => [''],
    'spaces' => ['   '],
    'control characters only' => ["\0\x07"],
    'Unicode separators' => ["\u{00A0}\u{3000}\u{2028}"],
]);

it('rejects a description over 5000 characters and accepts exactly 5000', function (): void {
    TimelineComponent::open($this->lead)->set('description', str_repeat('a', 5001))->call('save')->assertHasErrors(['description' => 'max']);

    expect(Activity::query()->count())->toBe(0);

    TimelineComponent::open($this->lead)->set('description', str_repeat('ñ', 5000))->call('save')->assertHasNoErrors();

    expect(Activity::query()->count())->toBe(1);
});

it('rejects an unknown activity type', function (): void {
    TimelineComponent::open($this->lead)->set('type', 'fax')->set('description', 'x')->call('save')->assertHasErrors('type');

    expect(Activity::query()->count())->toBe(0);
});

it('validates occurred_at as a date', function (string $value): void {
    TimelineComponent::open($this->lead)->set('description', 'x')->set('occurredAt', $value)->call('save')->assertHasErrors('occurredAt');

    expect(Activity::query()->count())->toBe(0);
})->with([
    'empty' => [''],
    'words' => ['yesterday'],
    'not a calendar day' => ['2026-02-30T10:00'],
    'wrong format' => ['20/11/2026 10:30'],
    'date without time' => ['2026-11-20'],
    'with seconds and offset' => ['2026-11-20T10:30:00+05:00'],
    'before 1900' => ['1899-12-31T23:59'],
    'year zero' => ['0000-01-01T00:00'],
]);

it('rejects a moment more than one day in the future', function (): void {
    TimelineComponent::open($this->lead)->set('description', 'x')->set('occurredAt', '2026-11-24T12:01')->call('save')->assertHasErrors(['occurredAt' => 'before_or_equal']);

    expect(Activity::query()->count())->toBe(0);
});

it('accepts a moment up to one day in the future', function (): void {
    TimelineComponent::open($this->lead)->set('description', 'Scheduled')->set('occurredAt', '2026-11-24T12:00')->call('save')->assertHasNoErrors();

    expect(Activity::query()->count())->toBe(1);
});

it('stores text cleaned of control characters and invalid UTF-8 but keeps line breaks', function (): void {
    TimelineComponent::open($this->lead)->set('description', "line 1\r\nline\t2\0\x01 Ana\xC3lytical\n")->call('save')->assertHasNoErrors();

    $description = Activity::query()->sole()->description;

    expect($description)->toStartWith("line 1\nline\t2 Ana")
        ->and(mb_check_encoding($description, 'UTF-8'))->toBeTrue()
        ->and($description)->not->toContain("\0")
        ->and($description)->not->toEndWith("\n");
});

it('lists the activities of the lead newest first', function (): void {
    Activity::factory()->for($this->lead)->create(['description' => 'middle one', 'occurred_at' => '2026-11-10 10:00:00']);
    Activity::factory()->for($this->lead)->create(['description' => 'newest one', 'occurred_at' => '2026-11-12 10:00:00']);
    Activity::factory()->for($this->lead)->create(['description' => 'oldest one', 'occurred_at' => '2026-11-01 10:00:00']);

    TimelineComponent::open($this->lead)->assertSeeInOrder(['newest one', 'middle one', 'oldest one']);
});

it('puts a freshly logged past activity in its place by moment, not at the top', function (): void {
    Activity::factory()->for($this->lead)->create(['description' => 'recent one', 'occurred_at' => '2026-11-22 10:00:00']);

    TimelineComponent::open($this->lead)->set('description', 'back then')->set('occurredAt', '2026-10-01T09:00')->call('save')
        ->assertSeeInOrder(['recent one', 'back then']);
});

it('does not show activities of other leads', function (): void {
    Activity::factory()->for($this->lead)->create(['description' => 'about this lead']);
    Activity::factory()->for(Lead::factory())->create(['description' => 'about another lead']);

    TimelineComponent::open($this->lead)->assertSee('about this lead')->assertDontSee('about another lead');
});

it('shows an empty state when the lead has no activities', function (): void {
    TimelineComponent::open($this->lead)->assertSee('Aún no hay actividades');
});

it('paginates the timeline by 20 and goes back to the first page after logging', function (): void {
    foreach (range(1, 25) as $i) {
        Activity::factory()->for($this->lead)->create(['description' => "entry-{$i}-end", 'occurred_at' => CarbonImmutable::parse('2026-11-01')->addHours($i)]);
    }

    $component = TimelineComponent::open($this->lead)->assertSee('entry-25-end')->assertSee('entry-6-end')->assertDontSee('entry-5-end');

    $component->call('nextPage')->assertSee('entry-5-end')->assertDontSee('entry-25-end');

    $component->set('description', 'logged on page two')->call('save')->assertSet('paginators.page', 1)->assertSee('logged on page two');
});

it('shows the description escaped and keeps its line breaks', function (): void {
    Activity::factory()->for($this->lead)->create(['description' => "<script>alert('x')</script>\nsecond line"]);

    $html = TimelineComponent::open($this->lead)->html();

    expect($html)->toContain('&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;')
        ->and($html)->not->toContain('<script>alert')
        ->and($html)->toContain('whitespace-pre-line');
});

it('escapes the description it has just logged', function (): void {
    $html = TimelineComponent::open($this->lead)->set('description', '"><img src=x onerror=alert(1)>')->call('save')->html();

    expect($html)->toContain('&lt;img src=x onerror=alert(1)&gt;')->and($html)->not->toContain('<img src=x');
});

it('does not let the client change which lead the timeline belongs to', function (): void {
    $other = Lead::factory()->create();

    TimelineComponent::open($this->lead)->set('leadId', $other->id);
})->throws(CannotUpdateLockedPropertyException::class);

it('answers 422 to payloads the form never sends', function (string $path, mixed $value): void {
    TimelineComponent::open($this->lead)->set($path, $value)->assertStatus(422);
})->with([
    'array description' => ['description', ['x']],
    'null description' => ['description', null],
    'array type' => ['type', ['x']],
    'number as type' => ['type', 5],
    'array moment' => ['occurredAt', ['2026-11-20T10:30']],
    'nested description' => ['description.0', 'x'],
    'array page' => ['paginators.page', ['1']],
    'unknown page name' => ['paginators.other', '1'],
]);

it('asks the activity policy before logging', function (): void {
    $asked = [];
    Gate::before(static function (mixed $user, string $ability) use (&$asked): null {
        $asked[] = $ability;

        return null;
    });

    TimelineComponent::open($this->lead)->set('description', 'x')->call('save');

    expect($asked)->toContain('viewAny')->toContain('create');
});

it('forbids non-admins from opening the timeline', function (): void {
    $this->app['auth']->logout();
    $this->actingAs(Accounts::user());

    TimelineComponent::open($this->lead)->assertForbidden();
});

it('does not let a non-admin log an activity through the component', function (): void {
    $component = TimelineComponent::open($this->lead)->set('description', 'sneaky');

    $this->app['auth']->logout();
    $this->actingAs(Accounts::user());

    $component->call('save')->assertForbidden();

    expect(Activity::query()->count())->toBe(0);
});
