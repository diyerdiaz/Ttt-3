@php($lead = $this->lead)
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('leads.index') }}" wire:navigate class="text-sm text-gray-600 hover:underline">&larr; Volver a leads</a>
            <h1 class="mt-1 text-xl font-semibold">{{ $lead->name }}</h1>
        </div>
        <a href="{{ route('leads.edit', $lead->id) }}" wire:navigate class="rounded bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700">Editar</a>
    </div>

    <dl class="grid gap-4 rounded border border-gray-200 bg-white p-4 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-xs uppercase tracking-wide text-gray-500">Estado</dt>
            <dd class="mt-1">{{ $lead->status->label() }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-gray-500">Empresa</dt>
            <dd class="mt-1">{{ $lead->company ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-gray-500">Correo</dt>
            <dd class="mt-1">{{ $lead->email ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-gray-500">Teléfono</dt>
            <dd class="mt-1">{{ $lead->phone ?? '—' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs uppercase tracking-wide text-gray-500">Notas</dt>
            <dd class="mt-1 whitespace-pre-line">{{ $lead->notes ?? '—' }}</dd>
        </div>
    </dl>

    @livewire(App\Http\Livewire\Activities\ActivityTimeline::class, ['leadId' => $lead->id])
</div>
