<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Memilih sumber video anime yang benar-benar hidup.
 *
 * Banyak CDN anime memblokir IP datacenter (Cloudflare 403) sementara host
 * lain normal. Service ini memeriksa kandidat lebih dulu, lalu menandai
 * apakah sebuah URL harus dialirkan langsung atau lewat relay NUC.
 */
class AnimeSourceResolverService
{
    private const RELAY_BASE = 'http://100.110.54.105:8765/video';
    private const SOCKS = 'socks5h://127.0.0.1:1055';
    private const PROBE_TIMEOUT = 3;
    private const CACHE_TTL = 600;

    /**
     * @param  array<int, array{url:string,label?:string,referer?:string}>  $candidates
     * @return array<int, array{url:string,via:string,label:string,status:int}>
     */
    public static function resolve(array $candidates, string $defaultReferer = 'https://mirainime.com/'): array
    {
        $candidates = array_values(array_filter($candidates, fn ($c) => !empty($c['url']) && str_starts_with($c['url'], 'http')));

        if (empty($candidates)) {
            return [];
        }

        $cacheKey = 'anime_src_' . md5(json_encode(array_column($candidates, 'url')));

        // Ingat jalur per host supaya episode berikutnya tidak perlu diperiksa ulang.
        $knownRoutes = [];
        $unknown = [];
        foreach ($candidates as $candidate) {
            $host = parse_url($candidate['url'], PHP_URL_HOST) ?: '';
            $route = $host ? Cache::get('anime_host_route_' . md5($host)) : null;

            if ($route) {
                $knownRoutes[$candidate['url']] = $route;
            } else {
                $unknown[] = $candidate;
            }
        }

        if (empty($unknown)) {
            $ordered = [];

            foreach ($candidates as $candidate) {
                $via = $knownRoutes[$candidate['url']] ?? 'direct';
                $ordered[] = [
                    'url' => $candidate['url'],
                    'via' => $via,
                    'label' => $candidate['label'] ?? ($via === 'relay' ? 'Relay' : 'Direct'),
                    'status' => 200,
                ];
            }

            return $ordered;
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($candidates, $defaultReferer, $knownRoutes) {
            $referer = $candidates[0]['referer'] ?? $defaultReferer;

            // Periksa jalur langsung dan relay sekaligus agar tidak menunggu dua kali.
            $probeInput = [];
            foreach ($candidates as $idx => $candidate) {
                $probeInput[$idx . '|direct'] = [
                    'url' => $candidate['url'],
                    'referer' => $candidate['referer'] ?? $referer,
                    'relay' => false,
                ];
                $probeInput[$idx . '|relay'] = [
                    'url' => $candidate['url'],
                    'referer' => $candidate['referer'] ?? $referer,
                    'relay' => true,
                ];
            }

            $probe = self::probeMany($probeInput, $referer);

            $results = [];
            foreach ($candidates as $idx => $candidate) {
                if (isset($knownRoutes[$candidate['url']])) {
                    $via = $knownRoutes[$candidate['url']];
                    $results[] = [
                        'url' => $candidate['url'],
                        'via' => $via,
                        'label' => $candidate['label'] ?? ($via === 'relay' ? 'Relay' : 'Direct'),
                        'status' => 200,
                    ];
                    continue;
                }

                $directOk = $probe[$idx . '|direct']['ok'] ?? false;
                $relayOk = $probe[$idx . '|relay']['ok'] ?? false;

                $host = parse_url($candidate['url'], PHP_URL_HOST) ?: '';
                if ($host) {
                    Cache::put('anime_host_route_' . md5($host), $directOk ? 'direct' : ($relayOk ? 'relay' : 'direct'), 1800);
                }

                if ($directOk) {
                    $results[] = [
                        'url' => $candidate['url'],
                        'via' => 'direct',
                        'label' => $candidate['label'] ?? 'Direct',
                        'status' => $probe[$idx . '|direct']['status'] ?? 200,
                    ];
                } elseif ($relayOk) {
                    $results[] = [
                        'url' => $candidate['url'],
                        'via' => 'relay',
                        'label' => $candidate['label'] ?? 'Relay',
                        'status' => $probe[$idx . '|relay']['status'] ?? 200,
                    ];
                }
            }

            if (empty($results)) {
                Log::warning('[anime-src] semua sumber gagal diperiksa', [
                    'urls' => array_column($candidates, 'url'),
                ]);

                // Biarkan pemutar mencoba sendiri (mungkin blokir sementara).
                return [[
                    'url' => $candidates[0]['url'],
                    'via' => 'relay',
                    'label' => $candidates[0]['label'] ?? 'Fallback',
                    'status' => 0,
                ]];
            }

            return $results;
        });
    }

    /**
     * Periksa beberapa URL sekaligus memakai curl_multi agar tidak lambat.
     *
     * @return array<int, array{ok:bool,status:int}>
     */
    private static function probeMany(array $probes, string $referer): array
    {
        $multi = curl_multi_init();
        $handles = [];

        foreach ($probes as $idx => $candidate) {
            $target = $candidate['url'];

            if (!empty($candidate['relay'])) {
                $target = self::RELAY_BASE . '?url=' . urlencode($target) . '&referer=' . urlencode($candidate['referer'] ?? $referer);
            }

            $ch = curl_init($target);
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => self::PROBE_TIMEOUT,
                CURLOPT_RANGE => '0-1',
                CURLOPT_NOBODY => false,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Accept: */*',
                    'Referer: ' . ($candidate['referer'] ?? $referer),
                ],
            ];

            if (!empty($candidate['relay'])) {
                $options[CURLOPT_PROXY] = self::SOCKS;
            }

            curl_setopt_array($ch, $options);
            curl_multi_add_handle($multi, $ch);
            $handles[$idx] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($multi, $running);
            if ($running) {
                curl_multi_select($multi, 0.2);
            }
        } while ($running > 0);

        $out = [];

        foreach ($handles as $idx => $ch) {
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $ok = in_array($status, [200, 206], true) && (str_contains($type, 'video') || str_contains($type, 'octet-stream') || str_contains($type, 'mpegurl'));

            $out[$idx] = ['ok' => $ok, 'status' => $status];
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);

        return $out;
    }
}
