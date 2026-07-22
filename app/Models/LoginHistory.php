<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device',
        'platform',
        'browser',
        'login_at'
    ];

    protected $casts = [
        'login_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parse User Agent and record login history for a user.
     */
    public static function record(int $userId, string $ipAddress, string $userAgent): self
    {
        $platform = self::parsePlatform($userAgent);
        $browser = self::parseBrowser($userAgent);
        $device = self::parseDevice($userAgent);

        return self::create([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device' => $device,
            'platform' => $platform,
            'browser' => $browser,
            'login_at' => now(),
        ]);
    }

    private static function parsePlatform(string $userAgent): string
    {
        $platforms = [
            '/windows nt 10/i' => 'Windows 10/11',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/macintosh|mac os x/i' => 'macOS',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone OS',
            '/ipod/i' => 'iPod OS',
            '/ipad/i' => 'iPad OS',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile',
        ];

        foreach ($platforms as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                return $value;
            }
        }

        return 'Unknown OS';
    }

    private static function parseBrowser(string $userAgent): string
    {
        $browsers = [
            '/msie/i' => 'Internet Explorer',
            '/firefox/i' => 'Firefox',
            '/safari/i' => 'Safari',
            '/chrome/i' => 'Chrome',
            '/edge/i' => 'Edge',
            '/opera/i' => 'Opera',
            '/netscape/i' => 'Netscape',
            '/maxthon/i' => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i' => 'Handheld Browser',
        ];

        // Specific detection order: Chrome & Safari both match each other's tokens usually
        if (preg_match('/edge|edg/i', $userAgent)) {
            return 'Edge';
        }
        if (preg_match('/chrome/i', $userAgent) && preg_match('/safari/i', $userAgent)) {
            return 'Chrome';
        }
        
        foreach ($browsers as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                return $value;
            }
        }

        return 'Unknown Browser';
    }

    private static function parseDevice(string $userAgent): string
    {
        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'Tablet';
        }
        if (preg_match('/mobile|phone|iphone|ipod|android|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'Mobile';
        }
        return 'Desktop';
    }
}
