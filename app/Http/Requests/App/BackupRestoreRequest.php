<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class BackupRestoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:web'],
            'confirmation' => ['required', 'string', 'max:120'],
        ];
    }
}
