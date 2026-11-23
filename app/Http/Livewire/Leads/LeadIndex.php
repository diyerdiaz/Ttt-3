<?php

declare(strict_types=1);

namespace App\Http\Livewire\Leads;

use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Models\Lead;
use App\Http\Support\TextInput;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\Response;

final class LeadIndex extends Component
{
    use WithPagination;

    private const int PER_PAGE = 25;

    private const int MAX_SEARCH_LENGTH = 100;

    #[Url(as: 'q', history: false)]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Lead::class);

        $this->search = TextInput::singleLine($this->search);
    }

    public function updating(string $name, mixed $value): void
    {
        $valid = match ($name) {
            'search' => is_string($value),
            'paginators.page' => is_int($value) || is_string($value),
            default => false,
        };

        if (! $valid) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->search = TextInput::singleLine($this->search);
    }

    public function render(LeadRepository $leads): View
    {
        Gate::authorize('viewAny', Lead::class);

        return view('livewire.leads.lead-index', [
            'leads' => $leads->search(mb_substr($this->search, 0, self::MAX_SEARCH_LENGTH), self::PER_PAGE),
        ])->title('Leads');
    }
}
