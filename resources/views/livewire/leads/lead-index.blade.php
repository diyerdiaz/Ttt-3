<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Leads</h1>
        <a href="{{ route('leads.create') }}" wire:navigate class="rounded bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700">Nuevo lead</a>
    </div>

    @if (session('status'))
        <p role="status" class="rounded bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('status') }}</p>
    @endif

    <div>
        <label for="search" class="sr-only">Buscar leads</label>
        <input id="search" type="search" wire:model.live.debounce.300ms="search" maxlength="100"
               placeholder="Buscar por nombre, correo o empresa"
               class="w-full rounded border border-gray-300 px-3 py-2 text-sm sm:max-w-md">
    </div>

    <div class="overflow-x-auto rounded border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th scope="col" class="px-3 py-2">Nombre</th>
                    <th scope="col" class="px-3 py-2">Correo</th>
                    <th scope="col" class="px-3 py-2">Teléfono</th>
                    <th scope="col" class="px-3 py-2">Empresa</th>
                    <th scope="col" class="px-3 py-2">Estado</th>
                    <th scope="col" class="px-3 py-2"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($leads as $lead)
                    <tr wire:key="lead-{{ $lead->id }}">
                        <td class="px-3 py-2 font-medium">{{ $lead->name }}</td>
                        <td class="px-3 py-2">{{ $lead->email }}</td>
                        <td class="px-3 py-2">{{ $lead->phone }}</td>
                        <td class="px-3 py-2">{{ $lead->company }}</td>
                        <td class="px-3 py-2">{{ $lead->status->label() }}</td>
                        <td class="px-3 py-2 text-right">
                            <a href="{{ route('leads.edit', $lead->id) }}" wire:navigate class="text-blue-700 hover:underline">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">No hay leads que mostrar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $leads->links() }}
</div>
