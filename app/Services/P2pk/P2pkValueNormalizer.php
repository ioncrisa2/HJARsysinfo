<?php

namespace App\Services\P2pk;

use Illuminate\Support\Str;

class P2pkValueNormalizer
{
    public function text(mixed $value): ?string
    {
        $text = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $text === '' ? null : $text;
    }

    public function key(mixed $value): string
    {
        return Str::upper(Str::ascii($this->text($value) ?? ''));
    }

    public function locationKey(mixed $value): string
    {
        $key = preg_replace('/\b(PROVINSI|PROPINSI|KABUPATEN|KAB|KOTA|KECAMATAN|KEC|KELURAHAN|KEL|DESA)\b/u', ' ', $this->exactLocationKey($value));

        return trim(preg_replace('/\s+/u', ' ', $key));
    }

    public function exactLocationKey(mixed $value): string
    {
        $key = preg_replace('/[^A-Z0-9]+/u', ' ', $this->key($value));

        return trim(preg_replace('/\s+/u', ' ', $key));
    }

    public function number(mixed $value): int|float|null
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^0-9,.-]/', '', (string) $value);
        if ($normalized === '' || $normalized === '-') {
            return null;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $commaIsDecimal = strrpos($normalized, ',') > strrpos($normalized, '.');
            $normalized = $commaIsDecimal
                ? str_replace(',', '.', str_replace('.', '', $normalized))
                : str_replace(',', '', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = $this->normalizeSingleSeparator($normalized, ',');
        } elseif (str_contains($normalized, '.')) {
            $normalized = $this->normalizeSingleSeparator($normalized, '.');
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    /** @return array{latitude: float, longitude: float}|null */
    public function coordinates(mixed $value): ?array
    {
        $text = $this->text($value);
        if (! $text) {
            return null;
        }

        $parts = preg_split('/\s*[,;]\s*/', $text);
        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        $latitude = (float) $parts[0];
        $longitude = (float) $parts[1];
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return compact('latitude', 'longitude');
    }

    /** @param array<string, mixed> $row */
    public function fingerprint(array $row): string
    {
        $normalized = collect($row)
            ->map(fn (mixed $value): string => $this->key($value))
            ->values()
            ->all();

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function normalizeSingleSeparator(string $value, string $separator): string
    {
        if (substr_count($value, $separator) > 1) {
            return str_replace($separator, '', $value);
        }

        [$whole, $fraction] = explode($separator, $value, 2);

        return strlen($fraction) === 3
            ? $whole.$fraction
            : $whole.'.'.$fraction;
    }
}
