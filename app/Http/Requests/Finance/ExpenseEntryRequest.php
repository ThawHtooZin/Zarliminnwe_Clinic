<?php

namespace App\Http\Requests\Finance;

use App\Models\ExpenseEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExpenseEntryRequest extends FormRequest
{
    /**
     * @return array<int, string>
     */
    public static function allowedRequestFields(): array
    {
        return [
            '_method',
            '_token',
            'expense_category_id',
            'amount',
            'expense_date',
            'payee',
            'payment_method',
            'description',
        ];
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'payee' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in(ExpenseEntry::paymentMethods())],
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
                        'Expense entries only accept category, amount, expense date, payee, payment method, and description.'
                    );
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function expenseEntryData(): array
    {
        return $this->safe()->only([
            'expense_category_id',
            'amount',
            'expense_date',
            'payee',
            'payment_method',
            'description',
        ]);
    }
}
