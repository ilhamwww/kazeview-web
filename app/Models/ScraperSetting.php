<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScraperSetting extends Model
{
    protected $fillable = [
        'anoboy_domain',
        'idlix_domain',
        'dramabuzz_api_url',
        'dramabuzz_api_key',
        'dramabuzz_network_statuses',
    ];

    protected $casts = [
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
}