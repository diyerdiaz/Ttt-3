<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Support\Accounts;
use Tests\Support\CsvDownload;
use Tests\Support\ExportProbe;

it('downloads a streamed csv with attachment headers', function (): void {
    $this->actingAs(Accounts::admin());
    Lead::factory()->create(['name' => 'Ada Lovelace']);
    $this->travelTo(now()->setDate(2026, 11, 23)->setTime(14, 5, 9));

    $response = ExportProbe::download($this);

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
        ->assertHeader('Content-Disposition', 'attachment; filename=leads-20261123-140509.csv')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->baseResponse)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and(str_starts_with($response->streamedContent(), CsvDownload::BOM))->toBeTrue();
});

it('exports all leads when no status filter is given', function (): void {
    $this->actingAs(Accounts::admin());
    Lead::factory()->create(['name' => 'First']);
    Lead::factory()->status(LeadStatus::Won)->create(['name' => 'Second']);
    Lead::factory()->status(LeadStatus::Lost)->create(['name' => 'Third']);

    $rows = CsvDownload::rows(ExportProbe::download($this)->streamedContent());

    expect($rows[0])->toBe(['ID', 'Nombre', 'Correo', 'Teléfono', 'Empresa', 'Estado', 'Notas', 'Creado'])
        ->and(array_column(array_slice($rows, 1), 1))->toBe(['First', 'Second', 'Third']);
});

it('exports only the requested statuses', function (): void {
    $this->actingAs(Accounts::admin());
    Lead::factory()->create(['name' => 'First']);
    Lead::factory()->status(LeadStatus::Won)->create(['name' => 'Second']);
    Lead::factory()->status(LeadStatus::Lost)->create(['name' => 'Third']);

    expect(ExportProbe::names(ExportProbe::download($this, ['status' => ['won']])))->toBe(['Second'])
        ->and(ExportProbe::names(ExportProbe::download($this, ['status' => ['won', 'new']])))->toBe(['First', 'Second'])
        ->and(ExportProbe::names(ExportProbe::download($this, ['status' => []])))->toBe(['First', 'Second', 'Third']);
});

it('rejects an invalid status filter with 422', function (string $queryString): void {
    $this->actingAs(Accounts::admin());

    $this->get('/leads/export?'.$queryString)->assertStatus(422);
})->with([
    'status is not an array' => 'status=won',
    'unknown value' => 'status[]=bogus',
    'out of enum, uppercase' => 'status[]=WON',
    'duplicates' => 'status[]=won&status[]=won',
    'nested arrays' => 'status[][]=won',
    'nested keyed arrays' => 'status[0][a]=won',
    'more than six' => 'status[]=new&status[]=contacted&status[]=qualified&status[]=proposal&status[]=won&status[]=lost&status[]=new',
    'empty value' => 'status[]=',
]);

it('answers 422 and not a redirect or a 500 even when the browser asks for html', function (): void {
    $this->actingAs(Accounts::admin());

    $this->from('/leads')->get('/leads/export?status=won', ['Accept' => 'text/html'])->assertStatus(422)->assertHeaderMissing('Location');
});

it('neutralizes formula payloads in exported names and notes', function (): void {
    $this->actingAs(Accounts::admin());
    Lead::factory()->create(['name' => '=1+1', 'notes' => '@SUM(A1)', 'company' => '  -2', 'phone' => '+34 600 123 456']);

    $rows = CsvDownload::rows(ExportProbe::download($this)->streamedContent());

    expect($rows[1][1])->toBe("'=1+1")
        ->and($rows[1][3])->toBe("'+34 600 123 456")
        ->and($rows[1][4])->toBe("'  -2")
        ->and($rows[1][6])->toBe("'@SUM(A1)");
});

it('forbids export to guests and non-admins', function (): void {
    Lead::factory()->create();

    $this->get('/leads/export')->assertRedirect('/login');

    $this->actingAs(Accounts::user());
    $this->get('/leads/export')->assertForbidden();
});

it('throttles repeated exports', function (): void {
    $this->actingAs(Accounts::admin());

    foreach (range(1, 10) as $attempt) {
        ExportProbe::download($this)->assertOk();
    }

    ExportProbe::download($this)->assertStatus(429);
});

it('resolves /leads/export to the export even if a /leads/{lead} route exists', function (): void {
    Route::get('/leads/{lead}', static fn (): string => 'show')->name('probe.show');

    expect(Route::getRoutes()->match(Request::create('/leads/export'))->getName())->toBe('leads.export')
        ->and(Route::getRoutes()->match(Request::create('/leads/7'))->getName())->toBe('probe.show');
});

it('offers the export on the lead listing', function (): void {
    $this->actingAs(Accounts::admin());

    $this->get('/leads')
        ->assertOk()
        ->assertSee(route('leads.export'), false)
        ->assertSee('Exportar CSV')
        ->assertSee('name="status[]"', false)
        ->assertSee('Ganado');
});
