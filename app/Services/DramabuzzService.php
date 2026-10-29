<?php

namespace App\Services;

use App\Models\ScraperSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DramabuzzService
{
    /**
     * Get all configured Dramabuzz API keys.
     *
     * @return string[]
     */
    public static function getKeys(): array
    {
        $setting = ScraperSetting::getInstance();
        return $setting->getDramabuzzApiKeysList();
    }

    /**
     * Get the currently active/working key.
     * Falls back to the first available key if no active key is cached.
     */
    public static function getActiveKey(): string
    {
        $keys = self::getKeys();
        if (empty($keys)) {
            return '';
        }

        $cachedKey = Cache::get('dramabuzz_active_key');
        if (!empty($cachedKey) && in_array($cachedKey, $keys, true)) {
            // Check if this key is not marked as failed
            if (!Cache::has('dramabuzz_key_failed_' . md5($cachedKey))) {
                return $cachedKey;
            }
        }

        // Return first non-failed key
        foreach ($keys as $key) {
            if (!Cache::has('dramabuzz_key_failed_' . md5($key))) {
                return $key;
            }
        }

        // If all are marked failed (fallback/grace), return the first configured key
        return $keys[0];
    }

    /**
     * Determine if a response indicates that the API key/token is expired or invalid.
     */
    public static function isKeyExpiredResponse(mixed $response, ?string $body = null): bool
    {
        $status = 200;

        if ($response instanceof Response) {
            $status = $response->status();
            $body = $body ?? $response->body();
        } elseif (is_int($response)) {
            $status = $response;
        }

        if (in_array($status, [401, 403], true)) {
            return true;
        }

        if (!empty($body)) {
            $lower = strtolower($body);
            if (
                str_contains($lower, 'invalid or expired') ||
                str_contains($lower, 'kode akses diperlukan') ||
                str_contains($lower, '"unauthorized"') ||
                str_contains($lower, 'token expired') ||
                str_contains($lower, 'api key expired') ||
                str_contains($lower, 'invalid token') ||
                str_contains($lower, 'invalid code')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark a key as failed/expired so other keys are prioritized.
     */
    public static function markKeyFailed(string $key): void
    {
        if (empty($key)) {
            return;
        }

        // Mark key failed for 15 minutes
        Cache::put('dramabuzz_key_failed_' . md5($key), true, now()->addMinutes(15));

        // If the failed key was the cached active key, clear it
        if (Cache::get('dramabuzz_active_key') === $key) {
            Cache::forget('dramabuzz_active_key');
        }

        Log::warning('Dramabuzz API Key marked as failed/expired: ' . substr($key, 0, 6) . '...');
    }

    /**
     * Mark a key as working and set it as active.
     */
    public static function markKeyWorking(string $key): void
    {
        if (empty($key)) {
            return;
        }

        Cache::forget('dramabuzz_key_failed_' . md5($key));
        Cache::put('dramabuzz_active_key', $key, now()->addDays(7));
    }

    /**
     * Execute a callable request with automatic failover across all configured keys.
     * $requestFn receives ($key) and should return Response, string, array, or mixed.
     *
     * @param callable $requestFn function(string $apiKey): mixed
     * @return mixed
     */
    public static function executeWithFailover(callable $requestFn)
    {
        $keys = self::getKeys();
        if (empty($keys)) {
            return $requestFn('');
        }

        $activeKey = self::getActiveKey();

        // Sort keys: active key first, then non-failed keys, then failed keys as last resort
        $orderedKeys = collect($keys)->sortBy(function ($k) use ($activeKey) {
            if ($k === $activeKey) {
                return 0;
            }
            if (Cache::has('dramabuzz_key_failed_' . md5($k))) {
                return 2;
            }
            return 1;
        })->values()->all();

        $lastResult = null;

        foreach ($orderedKeys as $key) {
            try {
                $result = $requestFn($key);

                if ($result instanceof Response) {
                    if (self::isKeyExpiredResponse($result)) {
                        self::markKeyFailed($key);
                        $lastResult = $result;
                        continue;
                    }

                    if ($result->successful()) {
                        self::markKeyWorking($key);
                        return $result;
                    }
                } elseif (is_string($result)) {
                    if (self::isKeyExpiredResponse(null, $result)) {
                        self::markKeyFailed($key);
                        $lastResult = $result;
                        continue;
                    }
                    self::markKeyWorking($key);
                    return $result;
                } elseif (is_array($result)) {
                    $jsonStr = json_encode($result);
                    if (self::isKeyExpiredResponse(null, $jsonStr)) {
                        self::markKeyFailed($key);
                        $lastResult = $result;
                        continue;
                    }
                    self::markKeyWorking($key);
                    return $result;
                }

                // If result is valid
                return $result;
            } catch (\Throwable $e) {
                Log::warning('Dramabuzz request error with key ' . substr($key, 0, 6) . '...: ' . $e->getMessage());
                $lastResult = null;
            }
        }

        return $lastResult;
    }
}
