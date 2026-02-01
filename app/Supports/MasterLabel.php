<?php

namespace App\Supports;

class MasterLabel
{
    public static function fromMap(?string $slug, array $map): string
    {
        if (! $slug) return '-';
        return $map[$slug] ?? str($slug)->replace('_', ' ')->replace('-', ' ')->title()->toString();
    }
}
