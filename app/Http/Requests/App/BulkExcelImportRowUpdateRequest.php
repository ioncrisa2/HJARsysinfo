<?php

namespace App\Http\Requests\App;

use App\Models\BulkExcelImportBatch;
use App\Models\BulkExcelImportRow;
use Illuminate\Validation\Rules\RequiredIf;

class BulkExcelImportRowUpdateRequest extends PembandingStoreRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');
        $row = $this->route('row');

        return $batch instanceof BulkExcelImportBatch
            && $row instanceof BulkExcelImportRow
            && $row->batch_id === $batch->id
            && (bool) $this->user()?->can('update', $batch);
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['tanggal_data']);

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_array($fieldRules) ? $fieldRules : [$fieldRules];
            $fieldRules = array_values(array_filter(
                $fieldRules,
                fn (mixed $rule): bool => $rule !== 'required' && ! $rule instanceof RequiredIf,
            ));

            if (! in_array('nullable', $fieldRules, true)) {
                array_unshift($fieldRules, 'nullable');
            }

            array_unshift($fieldRules, 'sometimes');
            $rules[$field] = $fieldRules;
        }

        $rules['remove_image'] = ['sometimes', 'boolean'];

        return $rules;
    }
}
