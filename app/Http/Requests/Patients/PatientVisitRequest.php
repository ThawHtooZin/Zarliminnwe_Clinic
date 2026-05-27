<?php

namespace App\Http\Requests\Patients;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PatientVisitRequest extends FormRequest
{
    /**
     * @return array<int, string>
     */
    public static function allowedRequestFields(): array
    {
        return [
            '_method',
            '_token',
            'patient_name',
            'age',
            'visited_at',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'patient_name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'visited_at' => ['required', 'date'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (array_keys($this->all()) as $field) {
                if (! in_array($field, self::allowedRequestFields(), true)) {
                    $validator->errors()->add(
                        $field,
                        'Patient visits only accept patient name, age, and visit datetime.'
                    );
                }
            }
        });
    }
}
