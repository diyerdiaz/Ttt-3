<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="page-title">Pipeline</h1>
        <a href="{{ route('leads.create') }}" wire:navigate class="btn-primary">Nuevo lead</a>
    </div>

    <div aria-live="assertive" role="status">
        @error('move')
            <p role="alert" class="alert-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="-mx-4 flex snap-x snap-mandatory items-start gap-3 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6 md:snap-none">
        @foreach ($statuses as $status)
            @php($cards = $this->columns[$status->value])
            <section wire:key="col-{{ $status->value }}" class="w-72 shrink-0 snap-start rounded-xl bg-zinc-100/70 p-2 dark:bg-zinc-900/50" aria-label="{{ $status->label() }}">
                <header class="mb-2 flex items-center justify-between px-2 pt-1">
                    <h2 class="flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <x-stage-badge :status="$status" dot-only />{{ $status->label() }}
                    </h2>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium tabular-nums text-zinc-600 shadow-xs dark:bg-zinc-800 dark:text-zinc-300" data-count="{{ $status->value }}">{{ count($cards) }}</span>
                </header>

                <ul data-status="{{ $status->value }}" class="kanban-list space-y-2 rounded-lg p-1"
                    x-data
                    x-init="Sortable.create($el, {
                        group: 'pipeline',
                        animation: 180,
                        easing: 'cubic-bezier(0.2, 0, 0, 1)',
                        ghostClass: 'kanban-ghost',
                        chosenClass: 'kanban-chosen',
                        dragClass: 'kanban-drag',
                        onEnd: (e) => $wire.moveLead(+e.item.dataset.id, e.to.dataset.status, [...e.to.children].map((li) => +li.dataset.id)),
                    })">
                    @foreach ($cards as $lead)
                        <li wire:key="lead-{{ $lead->id }}" data-id="{{ $lead->id }}" class="kanban-card">
                            <p class="font-medium leading-snug">{{ $lead->name }}</p>
                            @if ($lead->company)
                                <p class="mt-0.5 text-zinc-500 dark:text-zinc-400">{{ $lead->company }}</p>
                            @endif
                            <a href="{{ route('leads.edit', $lead->id) }}" wire:navigate class="link mt-2 inline-block text-xs">Editar</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
</div>
