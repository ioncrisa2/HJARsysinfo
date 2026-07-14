<?php

namespace App\Http\Requests\App;

use App\Actions\BulkExcelImport\BulkApplyBulkExcelImportRowsAction;
use App\Models\BulkExcelImportBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkExcelImportBulkApplyRequest extends FormRequest
{
    private const TABLES = [
        'status_pemberi_informasi_id' => 'master_status_pemberi_informasi',
        'bentuk_tanah_id' => 'master_bentuk_tanah',
        'posisi_tanah_id' => 'master_posisi_tanah',
        'kondisi_tanah_id' => 'master_kondisi_tanah',
        'topografi_id' => 'master_topografi',
        'dokumen_tanah_id' => 'master_dokumen_tanah',
        'peruntukan_id' => 'master_peruntukan',
    ];

    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch instanceof BulkExcelImportBatch
            && $this->user()?->can('update', $batch) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $table = self::TABLES[$this->input('field')] ?? self::TABLES['status_pemberi_informasi_id'];

        return [
            'field' => ['required', 'string', Rule::in(BulkApplyBulkExcelImportRowsAction::ALLOWED_FIELDS)],
            'value' => ['required', 'integer', Rule::exists($table, 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'field.required' => 'Pilih field yang ingin diterapkan.',
            'field.in' => 'Field ini tidak aman untuk diterapkan ke banyak data.',
            'value.required' => 'Pilih nilai yang ingin diterapkan.',
            'value.integer' => 'Nilai master data tidak valid.',
            'value.exists' => 'Nilai master data tidak ditemukan.',
        ];
    }
}
