@php($lead = $this->lead)
<div class="mx-auto max-w-3xl space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('leads.index') }}" wire:navigate class="muted text-sm transition hover:text-zinc-900 dark:hover:text-zinc-100">&larr; Volver a leads</a>
            <h1 class="page-title mt-2">{{ $lead->name }}</h1>
        </div>
        <a href="{{ route('leads.edit', $lead->id) }}" wire:navigate class="btn-primary">Editar</a>
    </div>

    <dl class="surface grid gap-5 p-5 text-sm sm:grid-cols-2 sm:p-6">
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide muted">Estado</dt>
            <dd class="mt-1.5"><x-stage-badge :status="$lead->status" /></dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide muted">Empresa</dt>
            <dd class="mt-1">{{ $lead->company ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide muted">Correo</dt>
            <dd class="mt-1">{{ $lead->email ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide muted">Teléfono</dt>
            <dd class="mt-1">{{ $lead->phone ?? '—' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs font-medium uppercase tracking-wide muted">Notas</dt>
            <dd class="mt-1 whitespace-pre-line">{{ $lead->notes ?? '—' }}</dd>
        </div>
    </dl>

    @livewire(App\Http\Livewire\Activities\ActivityTimeline::class, ['leadId' => $lead->id])
</div>
