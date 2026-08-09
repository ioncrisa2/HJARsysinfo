<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class BackupImportRequest extends FormRequest
{
    public function rules(): array
    {
        $maxKilobytes = (int) config('system_backup.max_package_megabytes', 1024) * 1024;

        return [
            'package' => [
                'required',
                'file',
                "max:{$maxKilobytes}",
                'extensions:sbackup',
            ],
        ];
    }
}
