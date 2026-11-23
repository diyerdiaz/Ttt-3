@use('App\Domain\Leads\Enums\LeadStatus')
<div class="mx-auto max-w-2xl space-y-6">
    <h1 class="page-title">{{ $leadId === null ? 'Nuevo lead' : 'Editar lead' }}</h1>

    <form wire:submit="save" class="surface space-y-5 p-5 sm:p-6" novalidate>
        <div>
            <label for="name" class="label">Nombre <span aria-hidden="true">*</span></label>
            <input id="name" type="text" wire:model.blur="form.name" maxlength="150" required
                   @error('form.name') aria-invalid="true" aria-describedby="name-error" @enderror
                   class="field">
            @error('form.name') <p id="name-error" class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="label">Correo</label>
                <input id="email" type="email" wire:model.blur="form.email" maxlength="254"
                       @error('form.email') aria-invalid="true" aria-describedby="email-error" @enderror
                       class="field">
                @error('form.email') <p id="email-error" class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="label">Teléfono</label>
                <input id="phone" type="tel" wire:model.blur="form.phone" maxlength="64"
                       @error('form.phone') aria-invalid="true" aria-describedby="phone-error" @enderror
                       class="field">
                @error('form.phone') <p id="phone-error" class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="company" class="label">Empresa</label>
                <input id="company" type="text" wire:model.blur="form.company" maxlength="150"
                       @error('form.company') aria-invalid="true" aria-describedby="company-error" @enderror
                       class="field">
                @error('form.company') <p id="company-error" class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status" class="label">Estado</label>
                <select id="status" wire:model="form.status"
                        @error('form.status') aria-invalid="true" aria-describedby="status-error" @enderror
                        class="field">
                    @foreach (LeadStatus::boardOrder() as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
                @error('form.status') <p id="status-error" class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="notes" class="label">Notas</label>
            <textarea id="notes" rows="4" wire:model.blur="form.notes" maxlength="5000"
                      @error('form.notes') aria-invalid="true" aria-describedby="notes-error" @enderror
                      class="field"></textarea>
            @error('form.notes') <p id="notes-error" class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button type="submit" class="btn-primary">Guardar</button>
            <a href="{{ route('leads.index') }}" wire:navigate class="btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
