<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="page-title">Leads</h1>
        <a href="{{ route('leads.create') }}" wire:navigate class="btn-primary">Nuevo lead</a>
    </div>

    <details class="surface group px-4 py-3 text-sm">
        <summary class="flex cursor-pointer list-none items-center justify-between rounded-md font-medium marker:hidden [&::-webkit-details-marker]:hidden">
            Exportar CSV
            <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" class="size-4 text-zinc-400 transition-transform group-open:rotate-180"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
        </summary>
        <form method="GET" action="{{ route('leads.export') }}" class="mt-4 space-y-4">
            <fieldset>
                <legend class="muted">Estados a exportar (sin marcar ninguno se exportan todos)</legend>
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2.5">
                    @foreach (\App\Domain\Leads\Enums\LeadStatus::boardOrder() as $status)
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="status[]" value="{{ $status->value }}" class="size-4 rounded border-zinc-300 accent-accent-600 dark:border-zinc-600">
                            {{ $status->label() }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <button type="submit" class="btn-primary">Descargar CSV</button>
        </form>
    </details>

    @if (session('status'))
        <p role="status" class="alert-success">{{ session('status') }}</p>
    @endif

    <div class="relative sm:max-w-md">
        <label for="search" class="sr-only">Buscar leads</label>
        <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.58 3.58a.75.75 0 1 1-1.06 1.06l-3.58-3.58A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
        <input id="search" type="search" wire:model.live.debounce.300ms="search" maxlength="100"
               placeholder="Buscar por nombre, correo o empresa"
               class="field mt-0! pl-9">
    </div>

    <div class="surface overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                <tr>
                    <th scope="col" class="px-4 py-3 font-medium">Nombre</th>
                    <th scope="col" class="px-4 py-3 font-medium">Correo</th>
                    <th scope="col" class="px-4 py-3 font-medium">Teléfono</th>
                    <th scope="col" class="px-4 py-3 font-medium">Empresa</th>
                    <th scope="col" class="px-4 py-3 font-medium">Estado</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($leads as $lead)
                    <tr wire:key="lead-{{ $lead->id }}" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="whitespace-nowrap px-4 py-3 font-medium">
                            <a href="{{ route('leads.show', $lead->id) }}" wire:navigate class="rounded-sm hover:text-accent-700 hover:underline dark:hover:text-accent-300">{{ $lead->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $lead->email }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $lead->phone }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $lead->company }}</td>
                        <td class="px-4 py-3"><x-stage-badge :status="$lead->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('leads.edit', $lead->id) }}" wire:navigate class="link">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            <p>No hay leads que mostrar.</p>
                            @if ($search === '')
                                <p class="mt-1 text-xs">Aún no hay leads — <a href="{{ route('leads.create') }}" wire:navigate class="link">crea el primero</a>.</p>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $leads->links() }}
</div>
