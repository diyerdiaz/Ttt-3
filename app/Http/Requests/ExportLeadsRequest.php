<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadExportFilter;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

final class ExportLeadsRequest extends FormRequest
{
    public const int MAX_STATUSES = 6;

    public function authorize(): bool
    {
        return $this->user()?->can('export', Lead::class) === true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'array', 'max:'.self::MAX_STATUSES],
            'status.*' => ['bail', 'string', Rule::enum(LeadStatus::class), 'distinct'],
        ];
    }

    public function toFilter(): LeadExportFilter
    {
        $values = $this->validated('status', []);
        $statuses = [];

        foreach (is_array($values) ? $values : [] as $value) {
            $status = is_string($value) ? LeadStatus::tryFrom($value) : null;

            if ($status !== null) {
                $statuses[] = $status;
            }
        }

        return new LeadExportFilter($statuses);
    }

    /**
     * This is a download link, not a form: answer 422 instead of redirecting back with errors.
     */
    protected function failedValidation(Validator $validator): void
    {
        abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Filtro de exportación no válido.');
    }
}
