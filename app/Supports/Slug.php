<?php

namespace App\Supports;

use Illuminate\Support\Str;

class Slug
{
    public static function snake(?string $value): string
    {
        return (string) Str::of((string) $value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]+/u', '')
            ->trim()
            ->replaceMatches('/\s+/u', '_');
    }
}
