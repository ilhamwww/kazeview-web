<?php

namespace App\Services;

use App\Models\ScraperSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MediaContentSanitizerService
{
    /**
     * Dapatkan daftar kata kunci dewasa yang sedang aktif (di-cache 1 jam)
     */
    public static function getKeywords(): array
    {
        return Cache::remember('kz_adult_keywords_list', 3600, function () {
            try {
                $setting = ScraperSetting::getInstance();
                return $setting->getAdultKeywordsList();
            } catch (\Throwable $e) {
                return ScraperSetting::DEFAULT_ADULT_KEYWORDS;
            }
        });
    }

    /**
     * Cek apakah fitur sensor aktif
     */
    public static function isEnabled(): bool
    {
        return Cache::remember('kz_adult_filter_enabled', 3600, function () {
            try {
                $setting = ScraperSetting::getInstance();
                return (bool) ($setting->adult_filter_enabled ?? true);
            } catch (\Throwable $e) {
                return true;
            }
        });
    }

    /**
     * Cek apakah sebuah teks / array data mengandung konten dewasa
     * Return nama kata kunci terlarang jika ditemukan, atau null jika bersih
     */
    public static function findMatchedKeyword(string|array|null $input): ?string
    {
        if (!self::isEnabled() || empty($input)) {
            return null;
        }

        // 1. Pemeriksaan eksplisit pada Genre / Kategori (jika input berupa array)
        if (is_array($input)) {
            $genresToCheck = [];
            if (!empty($input['genres']) && is_array($input['genres'])) {
                foreach ($input['genres'] as $g) {
                    $genresToCheck[] = is_array($g) ? ($g['name'] ?? '') : (string) $g;
                }
            }
            if (!empty($input['genre'])) {
                $genresToCheck[] = (string) $input['genre'];
            }
            if (!empty($input['categories']) && is_array($input['categories'])) {
                foreach ($input['categories'] as $c) {
                    $genresToCheck[] = is_array($c) ? ($c['name'] ?? '') : (string) $c;
                }
            }
            if (!empty($input['category'])) {
                $genresToCheck[] = (string) $input['category'];
            }

            $strictlyForbiddenGenres = [
                'semi', 'film semi', 'semi indonesia', 'semi jepang', 'semi korea', 'semi barat',
                'erotic', 'erotis', 'adult', '18+', 'bokep', 'hentai', 'ecchi', 'kelas bintang', 'dewasa'
            ];

            foreach ($genresToCheck as $genreStr) {
                $gLower = mb_strtolower(trim($genreStr), 'UTF-8');
                if ($gLower === '') continue;
                foreach ($strictlyForbiddenGenres as $fg) {
                    if ($gLower === $fg || str_contains($gLower, $fg)) {
                        return "Genre: {$genreStr}";
                    }
                }
            }

            // Cek juga URL jika mengandung path kategori terlarang
            $urlToCheck = $input['url'] ?? ($input['watch_url'] ?? '');
            if ($urlToCheck) {
                $uLower = strtolower($urlToCheck);
                if (str_contains($uLower, '/film-semi/') || str_contains($uLower, '/semi/') || str_contains($uLower, 'kelas-bintang')) {
                    return "URL Path: {$urlToCheck}";
                }
            }
        }

        // 2. Kumpulkan seluruh string dari input (judul, slug, sinopsis, tag)
        $textToInspect = '';
        if (is_array($input)) {
            $textToInspect = implode(' ', array_filter([
                $input['title'] ?? '',
                $input['slug'] ?? '',
                $input['raw_slug'] ?? '',
                $input['synopsis'] ?? ($input['overview'] ?? ''),
                is_array($input['genres'] ?? null) ? implode(' ', $input['genres']) : ($input['genre'] ?? ''),
                is_array($input['tags'] ?? null) ? implode(' ', $input['tags']) : '',
            ]));
        } else {
            $textToInspect = (string) $input;
        }

        if (trim($textToInspect) === '') {
            return null;
        }

        $lowerText = mb_strtolower($textToInspect, 'UTF-8');
        // Normalisasi tanda baca menjadi spasi agar variasi seperti "18+", "bokep-indo", dsb mudah terdeteksi
        $cleanSpaced = preg_replace('/[_\-\.\:\/]+/', ' ', $lowerText);

        $keywords = self::getKeywords();

        foreach ($keywords as $kw) {
            $kwClean = trim(mb_strtolower($kw, 'UTF-8'));
            if ($kwClean === '') {
                continue;
            }

            // 1. Jika kata kunci mengandung spasi atau simbol bukan huruf-angka (misal: "kelas bintang", "18+", "sugar daddy")
            if (str_contains($kwClean, ' ') || !ctype_alnum(str_replace(' ', '', $kwClean))) {
                if (str_contains($lowerText, $kwClean) || str_contains($cleanSpaced, $kwClean)) {
                    return $kw;
                }
                continue;
            }

            // 2. Jika kata kunci tunggal (misal: "bokep", "semi", "jav", "binor")
            // Gunakan word-boundary regex agar tidak menabrak kata normal (misal "semi" tidak menabrak "semifinal")
            $pattern = '/(?:\b|_|-)' . preg_quote($kwClean, '/') . '(?:\b|_|-)/iu';
            if (preg_match($pattern, $lowerText) || preg_match($pattern, $cleanSpaced)) {
                return $kw;
            }

            // Kasus khusus kata-kata vulgar yang pasti terlarang di posisi mana pun (substring)
            $unconditionalSubstrings = ['bokep', 'ngocok', 'ngangkang', 'sange', 'binal'];
            if (in_array($kwClean, $unconditionalSubstrings, true) && str_contains($lowerText, $kwClean)) {
                return $kw;
            }
        }

        return null;
    }

    /**
     * Helper boolean: apakah bersih (aman)
     */
    public static function isClean(string|array|null $input): bool
    {
        return self::findMatchedKeyword($input) === null;
    }

    /**
     * Helper boolean: apakah terlarang (dewasa)
     */
    public static function isAdult(string|array|null $input): bool
    {
        return self::findMatchedKeyword($input) !== null;
    }

    /**
     * Filter array list kartu media, singkirkan semua yang terdeteksi dewasa
     */
    public static function filterList(array $items): array
    {
        if (!self::isEnabled()) {
            return $items;
        }

        $filtered = [];
        foreach ($items as $item) {
            $matched = self::findMatchedKeyword($item);
            if ($matched) {
                $title = is_array($item) ? ($item['title'] ?? 'Unknown') : (string) $item;
                Log::info("[MediaContentSanitizer] Blocked adult content: '{$title}' (matched keyword: '{$matched}')");
                continue;
            }
            $filtered[] = $item;
        }

        return array_values($filtered);
    }
}
