<?php

namespace App\Services;

/**
 * Normalisasi subtitle.
 *
 * Sumber (Moviebox/Indocast) mengirim SRT, sedangkan elemen <track> dan
 * Artplayer hanya menerima WebVTT. Tanpa konversi, subtitle dianggap rusak
 * dan tidak pernah tampil.
 */
class SubtitleService
{
    public static function toVtt(string $body): string
    {
        // Buang BOM dan normalisasi baris.
        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body) ?? $body;
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        if (str_starts_with(ltrim($body), 'WEBVTT')) {
            return $body;
        }

        $out = [];

        foreach (explode("\n", $body) as $line) {
            $trimmed = trim($line);

            // Nomor urut cue tidak dipakai di VTT.
            if ($trimmed !== '' && ctype_digit($trimmed)) {
                continue;
            }

            // 00:00:17,833 --> 00:00:18,917  ->  00:00:17.833 --> 00:00:18.917
            if (str_contains($line, '-->')) {
                $line = preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $line) ?? $line;
            }

            $out[] = $line;
        }

        $text = trim(implode("\n", $out));

        return "WEBVTT\n\n" . $text . "\n";
    }
}
