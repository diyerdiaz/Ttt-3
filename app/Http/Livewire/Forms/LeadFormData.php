<?php

declare(strict_types=1);

namespace App\Http\Livewire\Forms;

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadData;
use App\Domain\Leads\ValueObjects\PhoneNumber;
use App\Domain\Shared\ValueObjects\EmailAddress;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class LeadFormData extends Form
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $company = '';

    public string $notes = '';

    public string $status = 'new';

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'string', 'email:filter', 'max:254'],
            'phone' => ['nullable', 'string', 'max:'.PhoneNumber::MAX_LENGTH, $this->phoneRule()],
            'company' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(LeadStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'phone' => 'teléfono',
            'company' => 'empresa',
            'notes' => 'notas',
            'status' => 'estado',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'email' => 'El correo no tiene un formato válido.',
            'max' => 'El campo :attribute es demasiado largo.',
            'enum' => 'El estado seleccionado no es válido.',
        ];
    }

    public function fillFromLead(Lead $lead): void
    {
        $this->name = $lead->name;
        $this->email = (string) $lead->email;
        $this->phone = (string) $lead->phone;
        $this->company = (string) $lead->company;
        $this->notes = (string) $lead->notes;
        $this->status = $lead->status->value;
    }

    /**
     * Livewire skips TrimStrings, so surrounding whitespace is removed here before validating.
     */
    public function trimInputs(): void
    {
        foreach (['name', 'email', 'phone', 'company', 'notes'] as $field) {
            $this->{$field} = trim($this->{$field});
        }
    }

    public function toDto(): LeadData
    {
        $email = $this->blankToNull($this->email);
        $phone = $this->blankToNull($this->phone);

        return new LeadData(
            name: trim($this->name),
            email: $email === null ? null : EmailAddress::fromString($email),
            phone: $phone === null ? null : PhoneNumber::fromString($phone),
            company: $this->blankToNull($this->company),
            notes: $this->blankToNull($this->notes),
            status: LeadStatus::from($this->status),
        );
    }

    private function phoneRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && trim($value) !== '' && ! PhoneNumber::isValid($value)) {
                $fail('El teléfono no es válido: usa solo dígitos, espacios, +, - y paréntesis (mínimo 6 dígitos).');
            }
        };
    }

    private function blankToNull(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
