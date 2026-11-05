<?php

namespace App\Models;

use App\Services\DramabuzzService;
use Illuminate\Database\Eloquent\Model;

class ScraperSetting extends Model
{
    public const DEFAULT_ADULT_KEYWORDS = [
        'kelas bintang',
        'bokep',
        'semi',
        'ngocok',
        'ngangkang',
        'binor',
        'lendir',
        'sange',
        'binal',
        'staycation',
        'ranjang berdarah',
        'lubang berdarah',
        'rumput tetangga',
        'jav',
        '18+',
        'dewasa',
        'adegan panas',
        'vulgar',
        'film panas',
        'viral',
    ];

    protected $fillable = [
        'anoboy_domain',
        'idlix_domain',
        'dramabuzz_api_url',
        'dramabuzz_api_key',
        'dramabuzz_api_keys',
        'dramabuzz_network_statuses',
        'adult_filter_enabled',
        'adult_keywords',
    ];

    protected $casts = [
        'dramabuzz_api_keys' => 'array',
        'dramabuzz_network_statuses' => 'array',
        'adult_keywords' => 'array',
        'adult_filter_enabled' => 'boolean',
    ];

    /**
     * Get the singleton instance or create with defaults
     */
    public static function getInstance(): self
    {
        $instance = self::first();
        if (!$instance) {
            $instance = self::create([
                'anoboy_domain' => 'https://anoboy.be/',
                'idlix_domain' => 'https://z2.idlixku.com/',
                'dramabuzz_api_url' => 'https://api.dramabuzz.sbs/api/status',
                'dramabuzz_api_key' => '5193CD21848193E43FC399BA4D73BB13',
                'dramabuzz_api_keys' => ['5193CD21848193E43FC399BA4D73BB13'],
                'adult_filter_enabled' => true,
                'adult_keywords' => self::DEFAULT_ADULT_KEYWORDS,
            ]);
        } elseif (empty($instance->adult_keywords)) {
            $instance->adult_keywords = self::DEFAULT_ADULT_KEYWORDS;
            $instance->save();
        }
        return $instance;
    }

    /**
     * Get the active adult blacklist keywords list
     */
    public function getAdultKeywordsList(): array
    {
        if (is_array($this->adult_keywords) && !empty($this->adult_keywords)) {
            return array_values(array_filter(array_map('trim', $this->adult_keywords)));
        }
        return self::DEFAULT_ADULT_KEYWORDS;
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
