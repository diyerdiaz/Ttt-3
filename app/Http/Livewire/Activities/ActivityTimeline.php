<?php

declare(strict_types=1);

namespace App\Http\Livewire\Activities;

use App\Application\Activities\Actions\LogActivity;
use App\Domain\Activities\Contracts\ActivityRepository;
use App\Domain\Activities\Enums\ActivityType;
use App\Domain\Activities\Models\Activity;
use App\Domain\Activities\ValueObjects\ActivityData;
use App\Http\Support\TextInput;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

final class ActivityTimeline extends Component
{
    use WithPagination;

    private const int PER_PAGE = 20;

    private const string MOMENT_FORMAT = 'Y-m-d\TH:i';

    private const string EARLIEST_MOMENT = '1900-01-01T00:00';

    #[Locked]
    public int $leadId;

    public string $type = '';

    public string $description = '';

    public string $occurredAt = '';

    public function mount(int $leadId): void
    {
        Gate::authorize('viewAny', Activity::class);

        $this->leadId = $leadId;
        $this->occurredAt = $this->defaultMoment();
    }

    public function updating(string $name, mixed $value): void
    {
        $valid = match ($name) {
            'type', 'description', 'occurredAt' => is_string($value),
            'paginators.page' => is_int($value) || is_string($value),
            default => false,
        };

        if (! $valid) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['type', 'description', 'occurredAt'], true)) {
            $this->sanitize();
        }
    }

    public function save(LogActivity $log): void
    {
        Gate::authorize('create', Activity::class);

        $this->sanitize();
        $this->validate();

        $log($this->leadId, new ActivityData($this->description, $this->moment(), ActivityType::tryFrom($this->type)));

        $this->reset('type', 'description');
        $this->occurredAt = $this->defaultMoment();
        $this->resetPage();
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::enum(ActivityType::class)],
            'description' => ['required', 'string', 'max:'.ActivityData::DESCRIPTION_MAX_LENGTH],
            'occurredAt' => [
                'required',
                'date_format:'.self::MOMENT_FORMAT,
                'after_or_equal:'.self::EARLIEST_MOMENT,
                'before_or_equal:'.CarbonImmutable::now()->addDay()->format(self::MOMENT_FORMAT),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['type' => 'tipo', 'description' => 'descripción', 'occurredAt' => 'fecha'];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'max' => 'La descripción es demasiado larga (máximo 5000 caracteres).',
            'enum' => 'El tipo seleccionado no es válido.',
            'date_format' => 'La fecha no tiene un formato válido.',
            'after_or_equal' => 'La fecha es demasiado antigua.',
            'before_or_equal' => 'La fecha no puede ser más de un día en el futuro.',
        ];
    }

    public function render(ActivityRepository $activities): View
    {
        Gate::authorize('viewAny', Activity::class);

        return view('livewire.activities.activity-timeline', [
            'activities' => $activities->forLead($this->leadId, self::PER_PAGE),
            'types' => ActivityType::cases(),
        ]);
    }

    private function sanitize(): void
    {
        $this->type = TextInput::singleLine($this->type);
        $this->occurredAt = TextInput::singleLine($this->occurredAt);
        $this->description = TextInput::multiLine($this->description);
    }

    private function defaultMoment(): string
    {
        return CarbonImmutable::now()->format(self::MOMENT_FORMAT);
    }

    private function moment(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!'.self::MOMENT_FORMAT, $this->occurredAt, config()->string('app.timezone'))
            ?: throw new LogicException('The moment was validated before being parsed.');
    }
}
