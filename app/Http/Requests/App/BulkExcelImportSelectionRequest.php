<?php

namespace App\Http\Requests\App;

use App\Models\BulkExcelImportBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkExcelImportSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch instanceof BulkExcelImportBatch
            && (bool) $this->user()?->can('update', $batch);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['set_rows', 'select_all', 'clear_all', 'select_ready'])],
            'row_ids' => ['exclude_unless:action,set_rows', 'required_if:action,set_rows', 'array', 'min:1', 'max:100'],
            'row_ids.*' => ['integer', 'distinct'],
            'is_selected' => ['exclude_unless:action,set_rows', 'required_if:action,set_rows', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Pilih tindakan untuk data yang ditandai.',
            'row_ids.required_if' => 'Pilih minimal satu data.',
            'row_ids.max' => 'Maksimal 100 data dapat diubah sekaligus.',
        ];
    }
}
