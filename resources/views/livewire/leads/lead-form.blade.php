@use('App\Domain\Leads\Enums\LeadStatus')
<div class="mx-auto max-w-2xl space-y-4">
    <h1 class="text-xl font-semibold">{{ $leadId === null ? 'Nuevo lead' : 'Editar lead' }}</h1>

    <form wire:submit="save" class="space-y-4 rounded border border-gray-200 bg-white p-4" novalidate>
        <div>
            <label for="name" class="block text-sm font-medium">Nombre <span aria-hidden="true">*</span></label>
            <input id="name" type="text" wire:model.blur="form.name" maxlength="150" required
                   @error('form.name') aria-invalid="true" aria-describedby="name-error" @enderror
                   class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            @error('form.name') <p id="name-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="block text-sm font-medium">Correo</label>
                <input id="email" type="email" wire:model.blur="form.email" maxlength="254"
                       @error('form.email') aria-invalid="true" aria-describedby="email-error" @enderror
                       class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                @error('form.email') <p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium">Teléfono</label>
                <input id="phone" type="tel" wire:model.blur="form.phone" maxlength="64"
                       @error('form.phone') aria-invalid="true" aria-describedby="phone-error" @enderror
                       class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                @error('form.phone') <p id="phone-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="company" class="block text-sm font-medium">Empresa</label>
                <input id="company" type="text" wire:model.blur="form.company" maxlength="150"
                       @error('form.company') aria-invalid="true" aria-describedby="company-error" @enderror
                       class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                @error('form.company') <p id="company-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium">Estado</label>
                <select id="status" wire:model="form.status"
                        @error('form.status') aria-invalid="true" aria-describedby="status-error" @enderror
                        class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                    @foreach (LeadStatus::boardOrder() as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
                @error('form.status') <p id="status-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium">Notas</label>
            <textarea id="notes" rows="4" wire:model.blur="form.notes" maxlength="5000"
                      @error('form.notes') aria-invalid="true" aria-describedby="notes-error" @enderror
                      class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"></textarea>
            @error('form.notes') <p id="notes-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Guardar</button>
            <a href="{{ route('leads.index') }}" wire:navigate class="text-sm text-gray-600 hover:underline">Cancelar</a>
        </div>
    </form>
</div>
