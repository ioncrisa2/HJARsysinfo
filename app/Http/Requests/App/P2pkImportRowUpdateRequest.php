<?php

namespace App\Http\Requests\App;

use App\Models\P2pkImportBatch;
use App\Models\P2pkImportRow;
use Illuminate\Validation\Rules\RequiredIf;

class P2pkImportRowUpdateRequest extends PembandingStoreRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');
        $row = $this->route('row');

        return $batch instanceof P2pkImportBatch
            && $row instanceof P2pkImportRow
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
