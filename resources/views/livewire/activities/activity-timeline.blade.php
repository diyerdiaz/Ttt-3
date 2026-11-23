<section class="space-y-4" aria-labelledby="activities-heading">
    <h2 id="activities-heading" class="text-lg font-semibold">Actividad</h2>

    <form wire:submit="save" class="space-y-4 rounded border border-gray-200 bg-white p-4" novalidate>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="activity-type" class="block text-sm font-medium">Tipo</label>
                <select id="activity-type" wire:model.blur="type"
                        @error('type') aria-invalid="true" aria-describedby="activity-type-error" @enderror
                        class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Sin tipo</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('type') <p id="activity-type-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="activity-moment" class="block text-sm font-medium">Fecha y hora <span aria-hidden="true">*</span></label>
                <input id="activity-moment" type="datetime-local" wire:model.blur="occurredAt" required
                       @error('occurredAt') aria-invalid="true" aria-describedby="activity-moment-error" @enderror
                       class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                @error('occurredAt') <p id="activity-moment-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="activity-description" class="block text-sm font-medium">Descripción <span aria-hidden="true">*</span></label>
            <textarea id="activity-description" wire:model.blur="description" rows="3" maxlength="5000" required
                      @error('description') aria-invalid="true" aria-describedby="activity-description-error" @enderror
                      class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"></textarea>
            @error('description') <p id="activity-description-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Registrar actividad</button>
    </form>

    <ol class="space-y-3">
        @forelse ($activities as $activity)
            <li wire:key="activity-{{ $activity->id }}" class="rounded border border-gray-200 bg-white p-3 text-sm">
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                    @if ($activity->type)
                        <span class="rounded bg-gray-100 px-2 py-0.5 font-medium text-gray-700">{{ $activity->type->label() }}</span>
                    @endif
                    <time datetime="{{ $activity->occurred_at->toIso8601String() }}">{{ $activity->occurred_at->format('d/m/Y H:i') }}</time>
                </div>
                <p class="mt-1 whitespace-pre-line">{{ $activity->description }}</p>
            </li>
        @empty
            <li class="rounded border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">Aún no hay actividades registradas.</li>
        @endforelse
    </ol>

    {{ $activities->links() }}
</section>
