<?php

namespace App\Http\Requests\App;

use App\Enums\BackupType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BackupCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(BackupType::class)],
        ];
    }
}
