<?php

namespace App\Http\Requests\Finance;

use App\Models\IncomeEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IncomeEntryRequest extends FormRequest
{
    /**
     * @return array<int, string>
     */
    public static function allowedRequestFields(): array
    {
        return [
            '_method',
            '_token',
            'income_category_id',
            'patient_visit_id',
            'patient_visit_record_id',
            'amount',
            'payment_method',
            'received_at',
            'description',
        ];
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'income_category_id' => ['required', 'integer', 'exists:income_categories,id'],
            'patient_visit_id' => ['nullable', 'integer', 'exists:patient_visit_records,id'],
            'patient_visit_record_id' => ['nullable', 'integer', 'exists:patient_visit_records,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(IncomeEntry::paymentMethods())],
            'received_at' => ['required', 'date'],
            'description' => ['nullable', 'string'],
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
                        'Income entries only accept category, optional patient visit, amount, payment method, received time, and description.'
                    );
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function incomeEntryData(): array
    {
        $data = $this->safe()->only([
            'income_category_id',
            'patient_visit_id',
            'patient_visit_record_id',
            'amount',
            'payment_method',
            'received_at',
            'description',
        ]);

        $visitRecordId = $data['patient_visit_record_id'] ?? $data['patient_visit_id'] ?? null;
        unset($data['patient_visit_id']);

        $data['patient_visit_record_id'] = blank($visitRecordId) ? null : (int) $visitRecordId;

        return $data;
    }
}
