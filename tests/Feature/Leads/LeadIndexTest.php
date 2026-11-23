<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Http\Livewire\Leads\LeadForm;
use App\Http\Livewire\Leads\LeadIndex;
use Livewire\Livewire;
use Tests\Support\Accounts;
use Tests\Support\QueryCounter;

beforeEach(function (): void {
    $this->actingAs(Accounts::admin());
});

it('lists leads newest first with their status', function (): void {
    Lead::factory()->create(['name' => 'First lead']);
    Lead::factory()->status(LeadStatus::Won)->create(['name' => 'Second lead']);

    $this->get('/leads')
        ->assertOk()
        ->assertSeeInOrder(['Second lead', 'First lead'])
        ->assertSee('Ganado');
});

it('shows an empty state when there are no leads', function (): void {
    $this->get('/leads')->assertOk()->assertSee('No hay leads');
});

it('lists and searches leads by name, email or company', function (): void {
    Lead::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@engines.test', 'company' => 'Analytical']);
    Lead::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@navy.test', 'company' => 'Cobol Inc']);

    $component = Livewire::test(LeadIndex::class)->assertSee('Ada Lovelace')->assertSee('Grace Hopper');

    $component->set('search', 'lovelace')->assertSee('Ada Lovelace')->assertDontSee('Grace Hopper');
    $component->set('search', 'NAVY.test')->assertSee('Grace Hopper')->assertDontSee('Ada Lovelace');
    $component->set('search', 'analytical')->assertSee('Ada Lovelace')->assertDontSee('Grace Hopper');
    $component->set('search', 'nobody')->assertDontSee('Ada Lovelace')->assertSee('No hay leads');
});

it('keeps the search term in the query string', function (): void {
    Lead::factory()->create(['name' => 'Ada Lovelace']);
    Lead::factory()->create(['name' => 'Grace Hopper']);

    $this->get('/leads?q=grace')->assertSee('Grace Hopper')->assertDontSee('Ada Lovelace');
});

it('paginates 25 leads per page and resets to page one when searching', function (): void {
    Lead::factory()->count(30)->create();
    Lead::factory()->create(['name' => 'Needle']);

    Livewire::test(LeadIndex::class)
        ->assertViewHas('leads', fn ($leads): bool => count($leads->items()) === 25 && $leads->total() === 31)
        ->call('gotoPage', 2)
        ->assertViewHas('leads', fn ($leads): bool => count($leads->items()) === 6)
        ->set('search', 'Needle')
        ->assertViewHas('leads', fn ($leads): bool => $leads->currentPage() === 1 && $leads->total() === 1);
});

it('escapes lead data in the list', function (): void {
    Lead::factory()->create(['name' => '<script>alert(1)</script>']);

    $this->get('/leads')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
});

it('links to the create and edit pages', function (): void {
    $lead = Lead::factory()->create();

    $this->get('/leads')->assertSee(route('leads.create'), false)->assertSee(route('leads.edit', $lead->id), false);
});

it('renders the list with a constant number of queries', function (): void {
    $render = fn (): int => QueryCounter::during(fn () => $this->get('/leads')->assertOk());

    Lead::factory()->count(3)->create();
    $few = $render();

    Lead::factory()->count(60)->create();
    $many = $render();

    expect($many)->toBe($few);
});

it('searches with a constant number of queries', function (): void {
    $render = fn (): int => QueryCounter::during(fn () => $this->get('/leads?q=a')->assertOk());

    Lead::factory()->count(3)->create(['name' => 'abc']);
    $few = $render();

    Lead::factory()->count(60)->create(['name' => 'abc']);

    expect($render())->toBe($few);
});

it('does not load the notes of every lead in the list', function (): void {
    Lead::factory()->create(['notes' => 'SECRET-NOTE-CONTENT']);

    $this->get('/leads')->assertOk()->assertDontSee('SECRET-NOTE-CONTENT');
});

it('forbids lead management to non-admins', function (): void {
    $lead = Lead::factory()->create();
    $this->app['auth']->logout();
    $this->actingAs(Accounts::user());

    foreach (['/leads', '/leads/create', "/leads/{$lead->id}/edit"] as $uri) {
        $this->get($uri)->assertForbidden();
    }

    Livewire::test(LeadIndex::class)->assertForbidden();
    Livewire::test(LeadForm::class)->assertForbidden();
    Livewire::test(LeadForm::class, ['lead' => $lead->id])->assertForbidden();
});

it('redirects guests to the login page', function (): void {
    $this->app['auth']->logout();

    foreach (['/leads', '/leads/create', '/leads/1/edit'] as $uri) {
        $this->get($uri)->assertRedirect('/login');
    }
});

it('does not let a non-admin save through the form component', function (): void {
    $this->app['auth']->logout();
    $this->actingAs(Accounts::user());

    Livewire::test(LeadForm::class)->set('form.name', 'Ada')->call('save')->assertForbidden();

    expect(Lead::query()->count())->toBe(0);
});

it('rejects non numeric lead ids in the edit route', function (): void {
    $this->get('/leads/abc/edit')->assertNotFound();
});
