<?php

declare(strict_types=1);

use App\Domain\Activities\Models\Activity;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Http\Livewire\Activities\ActivityTimeline;
use App\Http\Livewire\Leads\LeadShow;
use Livewire\Livewire;
use Tests\Support\Accounts;
use Tests\Support\QueryCounter;

beforeEach(function (): void {
    $this->actingAs(Accounts::admin());
});

it('shows the lead data with a link to edit it and the activity timeline', function (): void {
    $lead = Lead::factory()->status(LeadStatus::Qualified)->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@engines.test',
        'phone' => '+57 300 123 4567',
        'company' => 'Analytical',
        'notes' => "first line\nsecond line",
    ]);

    $this->get("/leads/{$lead->id}")
        ->assertOk()
        ->assertSee('Ada Lovelace')
        ->assertSee('ada@engines.test')
        ->assertSee('+57 300 123 4567')
        ->assertSee('Analytical')
        ->assertSee('Calificado')
        ->assertSee('whitespace-pre-line')
        ->assertSee(route('leads.edit', $lead->id))
        ->assertSee(route('leads.index'))
        ->assertSeeLivewire(ActivityTimeline::class);
});

it('names the route leads.show', function (): void {
    expect(route('leads.show', 5, absolute: false))->toBe('/leads/5');
});

it('shows the activities of the lead on its page', function (): void {
    $lead = Lead::factory()->create();
    Activity::factory()->for($lead)->create(['description' => 'called them back']);
    Activity::factory()->for(Lead::factory())->create(['description' => 'somebody else']);

    $this->get("/leads/{$lead->id}")->assertSee('called them back')->assertDontSee('somebody else');
});

it('responds 404 when the lead does not exist', function (): void {
    $this->get('/leads/999999')->assertNotFound();
});

it('rejects lead ids that are not plain numbers or do not fit an integer', function (string $uri): void {
    $this->get($uri)->assertNotFound();
})->with(['/leads/99999999999999999999', '/leads/1e3', '/leads/-1', '/leads/0x1', '/leads/abc']);

it('keeps /leads/create out of the show route', function (): void {
    $this->get('/leads/create')->assertOk()->assertDontSee('Actividad');
});

it('escapes lead data in the rendered page', function (): void {
    $lead = Lead::factory()->create(['name' => '<script>alert(1)</script>', 'notes' => '<img src=x onerror=alert(2)>']);

    $this->get("/leads/{$lead->id}")
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertSee('&lt;img src=x onerror=alert(2)&gt;', false)
        ->assertDontSee('<script>alert(1)', false)
        ->assertDontSee('<img src=x', false);
});

it('redirects guests to the login page', function (): void {
    $this->app['auth']->logout();

    $this->get('/leads/1')->assertRedirect('/login');
});

it('forbids non-admins, on the page and on the component', function (): void {
    $lead = Lead::factory()->create();
    $this->app['auth']->logout();
    $this->actingAs(Accounts::user());

    $this->get("/leads/{$lead->id}")->assertForbidden();
    Livewire::test(LeadShow::class, ['lead' => $lead->id])->assertForbidden();
});

it('runs the same number of queries with 3 and with 60 activities', function (): void {
    $few = Lead::factory()->create();
    $many = Lead::factory()->create();
    Activity::factory()->for($few)->count(3)->create();
    Activity::factory()->for($many)->count(60)->create();
    $this->get("/leads/{$few->id}")->assertOk();

    $withThree = QueryCounter::during(fn () => $this->get("/leads/{$few->id}")->assertOk());
    $withSixty = QueryCounter::during(fn () => $this->get("/leads/{$many->id}")->assertOk());

    expect($withThree)->toBe($withSixty)->and($withSixty)->toBeLessThanOrEqual(4);
});
