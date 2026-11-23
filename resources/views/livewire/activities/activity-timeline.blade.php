<section class="space-y-5" aria-labelledby="activities-heading">
    <h2 id="activities-heading" class="text-base font-semibold tracking-tight">Actividad</h2>

    <form wire:submit="save" class="surface space-y-4 p-5" novalidate>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="activity-type" class="label">Tipo</label>
                <select id="activity-type" wire:model.blur="type"
                        @error('type') aria-invalid="true" aria-describedby="activity-type-error" @enderror
                        class="field">
                    <option value="">Sin tipo</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('type') <p id="activity-type-error" class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="activity-moment" class="label">Fecha y hora <span aria-hidden="true">*</span></label>
                <input id="activity-moment" type="datetime-local" wire:model.blur="occurredAt" required
                       @error('occurredAt') aria-invalid="true" aria-describedby="activity-moment-error" @enderror
                       class="field">
                @error('occurredAt') <p id="activity-moment-error" class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="activity-description" class="label">Descripción <span aria-hidden="true">*</span></label>
            <textarea id="activity-description" wire:model.blur="description" rows="3" maxlength="5000" required
                      @error('description') aria-invalid="true" aria-describedby="activity-description-error" @enderror
                      class="field"></textarea>
            @error('description') <p id="activity-description-error" class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary">Registrar actividad</button>
    </form>

    <ol class="space-y-2.5">
        @forelse ($activities as $activity)
            <li wire:key="activity-{{ $activity->id }}" class="surface p-4 text-sm shadow-xs">
                <div class="flex flex-wrap items-center gap-2 text-xs muted">
                    @if ($activity->type)
                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $activity->type->label() }}</span>
                    @endif
                    @php($occurredAt = \App\Domain\Shared\ValueObjects\DisplayTimezone::toDisplay($activity->occurred_at))
                    <time datetime="{{ $occurredAt->toIso8601String() }}">{{ $occurredAt->format('d/m/Y H:i') }}</time>
                </div>
                <p class="mt-2 whitespace-pre-line leading-relaxed">{{ $activity->description }}</p>
            </li>
        @empty
            <li class="empty-state">Aún no hay actividades registradas. Registra la primera arriba.</li>
        @endforelse
    </ol>

    {{ $activities->links() }}
</section>
