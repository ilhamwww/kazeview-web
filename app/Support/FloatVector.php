<?php

namespace App\Support;

use InvalidArgumentException;

final class FloatVector
{
    /**
     * @param  array<int, int|float>  $values
     * @return array<int, float>
     */
    public static function normalize(array $values): array
    {
        if ($values === []) {
            throw new InvalidArgumentException('Embedding vector tidak boleh kosong.');
        }

        $squaredNorm = 0.0;

        foreach ($values as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw new InvalidArgumentException('Embedding harus berisi angka.');
            }

            $number = (float) $value;

            if (! is_finite($number)) {
                throw new InvalidArgumentException('Embedding mengandung angka yang tidak valid.');
            }

            $squaredNorm += $number * $number;
        }

        if ($squaredNorm <= 0.0) {
            throw new InvalidArgumentException('Embedding memiliki norm nol.');
        }

        $norm = sqrt($squaredNorm);

        return array_map(
            static fn (int|float $value): float => (float) $value / $norm,
            $values,
        );
    }

    /**
     * @param  array<int, int|float>  $values
     */
    public static function encode(array $values): string
    {
        $normalized = self::normalize($values);
        $binary = pack('g*', ...$normalized);

        // PostgreSQL bytea expects an escaped textual representation when the
        // value is bound by PDO as a string. Hex format contains ASCII only.
        return '\\x'.bin2hex($binary);
    }

    /**
     * @return array<int, float>
     */
    public static function decode(string $binary): array
    {
        if (str_starts_with($binary, '\\x')) {
            $decoded = hex2bin(substr($binary, 2));

            if ($decoded === false) {
                throw new InvalidArgumentException('Hex embedding tidak valid.');
            }

            $binary = $decoded;
        }

        if ($binary === '' || strlen($binary) % 4 !== 0) {
            throw new InvalidArgumentException('Binary embedding tidak valid.');
        }

        $values = unpack('g*', $binary);

        if ($values === false) {
            throw new InvalidArgumentException('Binary embedding tidak dapat dibaca.');
        }

        return array_values($values);
    }

    /**
     * Both vectors are expected to be normalized.
     *
     * @param  array<int, int|float>  $left
     * @param  array<int, int|float>  $right
     */
    public static function dot(array $left, array $right): float
    {
        if (count($left) !== count($right) || $left === []) {
            throw new InvalidArgumentException('Dimensi embedding tidak sama.');
        }

        $score = 0.0;

        foreach ($left as $index => $value) {
            $score += (float) $value * (float) $right[$index];
        }

        return max(-1.0, min(1.0, $score));
    }
}