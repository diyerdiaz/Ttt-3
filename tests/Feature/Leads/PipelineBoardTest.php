<?php

declare(strict_types=1);

use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Exceptions\InvalidStatusTransition;
use App\Domain\Leads\Models\Lead;
use App\Http\Livewire\Pipeline\PipelineBoard;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\Support\Accounts;
use Tests\Support\Board;
use Tests\Support\QueryCounter;

beforeEach(function (): void {
    $this->actingAs(Accounts::admin());
});

describe('rendering', function (): void {
    it('renders one column per status with leads ordered by position', function (): void {
        Lead::factory()->status(LeadStatus::Qualified)->create(['name' => 'Second', 'company' => 'Acme', 'position' => 4]);
        Lead::factory()->status(LeadStatus::Qualified)->create(['name' => 'First', 'position' => 1]);

        $this->get('/pipeline')
            ->assertOk()
            ->assertSeeInOrder(['Nuevo', 'Contactado', 'Calificado', 'Propuesta', 'Ganado', 'Perdido'])
            ->assertSeeInOrder(['First', 'Second'])
            ->assertSee('Acme')
            ->assertSee('data-status="qualified"', false)
            ->assertSee('Pipeline');
    });

    it('exposes one sortable list per status, in board order', function (): void {
        $html = $this->get('/pipeline')->assertOk()->getContent();

        preg_match_all('/data-status="([a-z]+)"/', (string) $html, $matches);

        expect($matches[1])->toBe(array_map(static fn (LeadStatus $s): string => $s->value, LeadStatus::boardOrder()));
    });

    it('marks each card with its id and links to its edit page', function (): void {
        $lead = Lead::factory()->create(['name' => 'Ada']);

        $this->get('/pipeline')
            ->assertSee('data-id="'.$lead->id.'"', false)
            ->assertSee('wire:key="lead-'.$lead->id.'"', false)
            ->assertSee('wire:key="col-new"', false)
            ->assertSee(route('leads.edit', $lead->id), false);
    });

    it('escapes lead data', function (): void {
        Lead::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->get('/pipeline')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    });

    it('shows how many leads each column holds without extra queries', function (): void {
        Lead::factory()->count(3)->status(LeadStatus::Won)->create();

        Livewire::test(PipelineBoard::class)->assertSeeHtml('data-count="won">3<');
    });

    it('renders the board with a constant number of queries', function (): void {
        $measure = static fn (): int => QueryCounter::during(static fn () => Livewire::test(PipelineBoard::class));

        Lead::factory()->count(5)->create();
        $few = $measure();

        foreach (LeadStatus::boardOrder() as $status) {
            Lead::factory()->count(34)->status($status)->create();
        }

        expect(Lead::query()->count())->toBe(209)->and($measure())->toBe($few);
    });
});

describe('moving leads', function (): void {
    it('persists the order after moveLead is called', function (): void {
        $leads = Board::persisted();

        Livewire::test(PipelineBoard::class)
            ->call('moveLead', $leads[1]->id, 'qualified', [$leads[4]->id, $leads[1]->id, $leads[5]->id])
            ->assertHasNoErrors();

        expect($leads[1]->fresh()?->status)->toBe(LeadStatus::Qualified)
            ->and(Lead::query()->where('status', 'qualified')->orderBy('position')->pluck('id')->all())->toBe([$leads[4]->id, $leads[1]->id, $leads[5]->id]);
    });

    it('renders the new order after the move', function (): void {
        $a = Lead::factory()->create(['name' => 'Alpha', 'position' => 0]);
        $b = Lead::factory()->create(['name' => 'Bravo', 'position' => 1]);

        Livewire::test(PipelineBoard::class)
            ->assertSeeInOrder(['Alpha', 'Bravo'])
            ->call('moveLead', $b->id, 'new', [$b->id, $a->id])
            ->assertSeeInOrder(['Bravo', 'Alpha']);
    });

    it('rejects an invalid payload with 422 and changes nothing', function (mixed $leadId, mixed $status, mixed $ordered): void {
        $leads = Board::persisted();
        $before = Lead::query()->orderBy('id')->get(['id', 'status', 'position'])->toArray();
        $leadId = $leadId === 'FIRST' ? $leads[1]->id : $leadId;
        $ordered = $ordered === 'WITH_FIRST' ? [$leads[1]->id, $leads[4]->id] : $ordered;

        Livewire::test(PipelineBoard::class)->call('moveLead', $leadId, $status, $ordered)->assertStatus(422);

        expect(Lead::query()->orderBy('id')->get(['id', 'status', 'position'])->toArray())->toBe($before);
    })->with([
        'unknown status' => ['FIRST', 'archived', 'WITH_FIRST'],
        'status of the wrong type' => ['FIRST', ['won'], 'WITH_FIRST'],
        'lead id not an integer' => ['abc', 'qualified', [1, 2]],
        'lead id of the wrong type' => [[1], 'qualified', [1, 2]],
        'lead id zero' => [0, 'qualified', [0]],
        'negative lead id' => [-1, 'qualified', [-1]],
        'ordered ids not an array' => ['FIRST', 'qualified', 'x'],
        'non integer ids' => ['FIRST', 'qualified', ['a', 'b']],
        'nested ids' => ['FIRST', 'qualified', [[1]]],
        'string ids' => ['FIRST', 'qualified', ['1', '4']],
        'duplicated ids' => ['FIRST', 'qualified', [1, 1, 4]],
        'moved lead missing from the list' => ['FIRST', 'qualified', [4, 5]],
        'empty list' => ['FIRST', 'qualified', []],
        'more than 1000 ids' => ['FIRST', 'qualified', range(1, 1001)],
    ]);

    it('accepts exactly 1000 ids in the payload shape and reports the stale board', function (): void {
        $lead = Lead::factory()->create();

        Livewire::test(PipelineBoard::class)
            ->call('moveLead', $lead->id, 'qualified', [$lead->id, ...range($lead->id + 1, $lead->id + 999)])
            ->assertStatus(200)
            ->assertSee('El tablero cambió');
    });

    it('answers 404 when the lead does not exist', function (): void {
        Livewire::test(PipelineBoard::class)->call('moveLead', 999999, 'new', [999999])->assertStatus(404);
    });

    it('shows an error and re-renders the board on a stale move', function (): void {
        $leads = Board::persisted();
        $foreign = Lead::factory()->status(LeadStatus::Qualified)->create(['name' => 'Added elsewhere', 'position' => 2]);

        $component = Livewire::test(PipelineBoard::class);
        Lead::query()->whereKey($foreign->id)->update(['position' => 7]);
        Lead::factory()->status(LeadStatus::Qualified)->create(['name' => 'Newest elsewhere', 'position' => 9]);

        $component->call('moveLead', $leads[1]->id, 'qualified', [$leads[4]->id, $leads[1]->id, $leads[5]->id, $foreign->id])
            ->assertSee('El tablero cambió')
            ->assertSee('Newest elsewhere');

        expect($leads[1]->fresh()?->status)->toBe(LeadStatus::New);
    });

    it('treats ids of leads that are in another column as a stale move', function (): void {
        $leads = Board::persisted();

        Livewire::test(PipelineBoard::class)
            ->call('moveLead', $leads[1]->id, 'qualified', [$leads[1]->id, $leads[4]->id, $leads[5]->id, $leads[2]->id])
            ->assertSee('El tablero cambió');

        expect($leads[2]->fresh()?->status)->toBe(LeadStatus::New);
    });

    it('shows an error when the transition is refused', function (): void {
        $lead = Lead::factory()->create();
        $this->mock(LeadBoardRepository::class, static function (MockInterface $board): void {
            $board->shouldReceive('columns')->andReturn(array_fill_keys(array_map(static fn (LeadStatus $s): string => $s->value, LeadStatus::boardOrder()), []));
            $board->shouldReceive('lockBoard')->once();
            $board->shouldReceive('findForUpdate')->once()->andThrow(InvalidStatusTransition::between(LeadStatus::New, LeadStatus::Won));
        });

        Livewire::test(PipelineBoard::class)
            ->call('moveLead', $lead->id, 'won', [$lead->id])
            ->assertSee('No se puede mover');
    });
});

describe('authorization', function (): void {
    it('redirects guests to login', function (): void {
        auth()->logout();

        $this->get('/pipeline')->assertRedirect('/login');
    });

    it('forbids non-admins', function (): void {
        $this->actingAs(Accounts::user())->get('/pipeline')->assertForbidden();
    });

    it('forbids moving leads to non-admins', function (): void {
        $lead = Lead::factory()->create();
        $component = Livewire::test(PipelineBoard::class);

        $this->actingAs(Accounts::user());

        $component->call('moveLead', $lead->id, 'won', [$lead->id])->assertForbidden();
        expect($lead->fresh()?->status)->toBe(LeadStatus::New);
    });

    it('asks the move ability for the lead and viewAny for the board', function (): void {
        $lead = Lead::factory()->create();
        $asked = [];
        Gate::before(static function (mixed $user, string $ability) use (&$asked): null {
            $asked[] = $ability;

            return null;
        });

        $component = Livewire::test(PipelineBoard::class);
        expect($asked)->toContain('viewAny');

        $component->call('moveLead', $lead->id, 'won', [$lead->id]);
        expect($asked)->toContain('move');
    });
});

it('answers 422 to any attempt to write a property from the client', function (string $path, mixed $value): void {
    Livewire::test(PipelineBoard::class)->set($path, $value)->assertStatus(422);
})->with([
    ['error', 'x'],
    ['columns', []],
    ['leads', []],
    ['moving', true],
]);

it('keeps the dashboard route as a redirect to the pipeline', function (): void {
    $this->get('/')->assertRedirect('/pipeline');
});

it('links to the pipeline from the navigation', function (): void {
    $this->get('/leads')->assertSee('href="'.route('pipeline').'"', false);
});
