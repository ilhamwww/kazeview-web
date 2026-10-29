<?php

namespace App\Models;

use App\Services\DramabuzzService;
use Illuminate\Database\Eloquent\Model;

class ScraperSetting extends Model
{
    protected $fillable = [
        'anoboy_domain',
        'idlix_domain',
        'dramabuzz_api_url',
        'dramabuzz_api_key',
        'dramabuzz_api_keys',
        'dramabuzz_network_statuses',
    ];

    protected $casts = [
        'dramabuzz_api_keys' => 'array',
        'dramabuzz_network_statuses' => 'array',
    ];

    /**
     * Get the singleton instance or create with defaults
     */
    public static function getInstance(): self
    {
        return self::first() ?? self::create([
            'anoboy_domain' => 'https://anoboy.be/',
            'idlix_domain' => 'https://z2.idlixku.com/',
            'dramabuzz_api_url' => 'https://api.dramabuzz.sbs/api/status',
            'dramabuzz_api_key' => '5193CD21848193E43FC399BA4D73BB13',
            'dramabuzz_api_keys' => ['5193CD21848193E43FC399BA4D73BB13'],
        ]);
    }

    /**
     * Get anoboy domain with trailing slash
     */
    public function getAnoboyDomainAttribute($value): string
    {
        $domain = rtrim($value, '/');
        return $domain . '/';
    }

    /**
     * Get idlix domain with trailing slash
     */
    public function getIdlixDomainAttribute($value): string
    {
        $domain = rtrim($value ?? 'https://z2.idlixku.com/', '/');
        return $domain . '/';
    }

    /**
     * Get the active/current Dramabuzz API key with automatic failover.
     */
    public function getDramabuzzApiKeyAttribute($value): string
    {
        $activeKey = DramabuzzService::getActiveKey();
        if (!empty($activeKey)) {
            return $activeKey;
        }

        return $value ?? '5193CD21848193E43FC399BA4D73BB13';
    }

    /**
     * Get all configured Dramabuzz API keys as a clean list of strings.
     *
     * @return string[]
     */
    public function getDramabuzzApiKeysList(): array
    {
        $rawKeys = $this->dramabuzz_api_keys;

        if (empty($rawKeys)) {
            $single = $this->attributes['dramabuzz_api_key'] ?? null;
            return !empty($single) ? [trim($single)] : ['5193CD21848193E43FC399BA4D73BB13'];
        }

        if (!is_array($rawKeys)) {
            $single = $this->attributes['dramabuzz_api_key'] ?? null;
            return !empty($single) ? [trim($single)] : ['5193CD21848193E43FC399BA4D73BB13'];
        }

        $result = [];
        foreach ($rawKeys as $item) {
            if (is_string($item) && trim($item) !== '') {
                $result[] = trim($item);
            } elseif (is_array($item) && !empty($item['key']) && is_string($item['key'])) {
                $result[] = trim($item['key']);
            }
        }

        if (empty($result)) {
            $single = $this->attributes['dramabuzz_api_key'] ?? null;
            if (!empty($single)) {
                $result[] = trim($single);
            }
        }

        return array_values(array_unique($result));
    }
}
