<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Pipeline</h1>
        <a href="{{ route('leads.create') }}" wire:navigate class="rounded bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700">Nuevo lead</a>
    </div>

    @error('move')
        <p role="alert" class="rounded bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</p>
    @enderror

    <div class="flex items-start gap-4 overflow-x-auto pb-4">
        @foreach ($statuses as $status)
            @php($cards = $this->columns[$status->value])
            <section wire:key="col-{{ $status->value }}" class="w-72 shrink-0 rounded-lg border border-gray-200 bg-gray-100 p-3" aria-label="{{ $status->label() }}">
                <header class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">{{ $status->label() }}</h2>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs text-gray-600" data-count="{{ $status->value }}">{{ count($cards) }}</span>
                </header>

                <ul data-status="{{ $status->value }}" class="min-h-16 space-y-2"
                    x-data
                    x-init="Sortable.create($el, {
                        group: 'pipeline',
                        animation: 150,
                        ghostClass: 'opacity-40',
                        onEnd: (e) => $wire.moveLead(+e.item.dataset.id, e.to.dataset.status, [...e.to.children].map((li) => +li.dataset.id)),
                    })">
                    @foreach ($cards as $lead)
                        <li wire:key="lead-{{ $lead->id }}" data-id="{{ $lead->id }}" class="cursor-grab rounded border border-gray-200 bg-white p-3 text-sm shadow-sm">
                            <p class="font-medium">{{ $lead->name }}</p>
                            @if ($lead->company)
                                <p class="text-gray-600">{{ $lead->company }}</p>
                            @endif
                            <a href="{{ route('leads.edit', $lead->id) }}" wire:navigate class="mt-1 inline-block text-xs text-blue-700 hover:underline">Editar</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
</div>
