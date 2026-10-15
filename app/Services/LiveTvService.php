<?php

namespace App\Services;

class LiveTvService
{
    /**
     * Daftar stasiun TV Indonesia lengkap dengan metadata logo, kategori, dan multiple stream server.
     */
    public static function getCuratedChannels(): array
    {
        return [
            [
                'id' => 'trans7',
                'name' => 'Trans7',
                'slug' => 'trans7',
                'category' => 'Nasional',
                'tagline' => 'Makin Lengkap, Makin Asik',
                'logo' => '/icons/tv/trans7.png',
                'description' => 'Stasiun televisi swasta nasional Indonesia yang menyajikan program berita, investigasi, komedi, dan edukasi.',
                'servers' => [
                    [
                        'name' => 'Server 1 (HLS Direct)',
                        'type' => 'hls',
                        'url' => 'https://video.detik.com/trans7/smil:trans7.smil/index.m3u8',
                        'quality' => '720p HD'
                    ],
                    [
                        'name' => 'Server 2 (Web Stream)',
                        'type' => 'iframe',
                        'url' => 'https://20.detik.com/watch/trans7',
                        'quality' => 'Adaptive'
                    ]
                ],
                'is_featured' => true,
            ],
            [
                'id' => 'transtv',
                'name' => 'Trans TV',
                'slug' => 'transtv',
                'category' => 'Nasional',
                'tagline' => 'Milik Kita Bersama',
                'logo' => '/icons/tv/transtv.png',
                'description' => 'Saluran televisi hiburan keluarga, bioskop Trans TV, variety show, dan informasi gaya hidup terkini.',
                'servers' => [
                    [
                        'name' => 'Server 1 (HLS Direct)',
                        'type' => 'hls',
                        'url' => 'https://video.detik.com/transtv/smil:transtv.smil/index.m3u8',
                        'quality' => '720p HD'
                    ],
                    [
                        'name' => 'Server 2 (Web Stream)',
                        'type' => 'iframe',
                        'url' => 'https://20.detik.com/watch/transtv',
                        'quality' => 'Adaptive'
                    ]
                ],
                'is_featured' => true,
            ],
            [
                'id' => 'kompastv',
                'name' => 'Kompas TV',
                'slug' => 'kompastv',
                'category' => 'Berita',
                'tagline' => 'Independen | Terpercaya',
                'logo' => '/icons/tv/kompastv.png',
                'description' => 'Saluran berita independen terpercaya dengan jangkauan siaran nasional dan investigasi mendalam.',
                'servers' => [
                    [
                        'name' => 'Server 1 (YouTube Live 1080p)',
                        'type' => 'youtube',
                        'url' => 'https://www.youtube.com/embed/DOOrIxw5xOw?autoplay=1&mute=0&rel=0&playsinline=1&origin=https://streaming.kazeview.com',
                        'video_id' => 'DOOrIxw5xOw',
                        'quality' => '1080p 60fps'
                    ],
                    [
                        'name' => 'Server 2 (HLS Direct)',
                        'type' => 'hls',
                        'url' => 'https://op-group1-swiftservehd-1.dens.tv/h/h234/02.m3u8',
                        'quality' => '720p HD'
                    ]
                ],
                'is_featured' => true,
            ],
            [
                'id' => 'metrotv',
                'name' => 'Metro TV',
                'slug' => 'metrotv',
                'category' => 'Berita',
                'tagline' => 'Knowledge to Elevate',
                'logo' => '/icons/tv/metrotv.png',
                'description' => 'Pelopor stasiun televisi berita 24 jam pertama di Indonesia dengan jurnalisme berkualitas.',
                'servers' => [
                    [
                        'name' => 'Server 1 (HLS Direct)',
                        'type' => 'hls',
                        'url' => 'https://edge.medcom.id/live-edge/smil:metro.smil/playlist.m3u8',
                        'quality' => '720p HD'
                    ],
                    [
                        'name' => 'Server 2 (YouTube Live)',
                        'type' => 'youtube',
                        'url' => 'https://www.youtube-nocookie.com/embed/vol6AKjgC1U?autoplay=1&mute=0',
                        'video_id' => 'vol6AKjgC1U',
                        'quality' => '1080p HD'
                    ]
                ],
                'is_featured' => true,
            ],
            [
                'id' => 'tvone',
                'name' => 'tvOne',
                'slug' => 'tvone',
                'category' => 'Berita',
                'tagline' => 'Memang Beda',
                'logo' => '/icons/tv/tvone.png',
                'description' => 'Saluran berita terdepan, talkshow politik, analisis tajam, dan siaran olahraga nasional.',
                'servers' => [
                    [
                        'name' => 'Server 1 (YouTube Live 1080p)',
                        'type' => 'youtube',
                        'url' => 'https://www.youtube-nocookie.com/embed/1W-WRa3GE1k?autoplay=1&mute=0',
                        'video_id' => '1W-WRa3GE1k',
                        'quality' => '1080p 60fps'
                    ],
                    [
                        'name' => 'Server 2 (HLS Direct)',
                        'type' => 'hls',
                        'url' => 'https://op-group1-swiftservehd-1.dens.tv/h/h40/02.m3u8',
                        'quality' => '720p HD'
                    ]
                ],
                'is_featured' => true,
            ],
            [
                'id' => 'tvri-nasional',
                'name' => 'TVRI Nasional',
                'slug' => 'tvri-nasional',
                'category' => 'Nasional',
                'tagline' => 'Media Pemersatu Bangsa',
                'logo' => '/icons/tv/tvri-nasional.png',
                'description' => 'Lembaga Penyiaran Publik televisi pertama di Indonesia yang menyatukan seluruh nusantara.',
                'servers' => [
                    [
                        'name' => 'Server 1 (Official OTT HLS)',
                        'type' => 'hls',
                        'url' => 'https://ott-balancer.tvri.go.id/live/eds/Nasional/hls/Nasional.m3u8',
                        'quality' => '1080p Full HD'
                    ]
                ],
                'is_featured' => true,
            ],
            [
                'id' => 'tvri-sport',
                'name' => 'TVRI Sport',
                'slug' => 'tvri-sport',
                'category' => 'Olahraga',
                'tagline' => 'Rumah Olahraga Indonesia',
                'logo' => '/icons/tv/tvri-sport.png',
                'description' => 'Saluran khusus olahraga nasional & internasional, bulu tangkis, sepak bola, dan atletik.',
                'servers' => [
                    [
                        'name' => 'Server 1 (Official OTT HLS)',
                        'type' => 'hls',
                        'url' => 'https://ott-balancer.tvri.go.id/live/eds/Sport/hls/Sport.m3u8',
                        'quality' => '1080p Full HD'
                    ]
                ],
                'is_featured' => false,
            ],
            [
                'id' => 'tvri-world',
                'name' => 'TVRI World',
                'slug' => 'tvri-world',
                'category' => 'Nasional',
                'tagline' => 'Connecting Indonesia to the World',
                'logo' => '/icons/tv/tvri-world.png',
                'description' => 'Saluran internasional berbahasa Inggris yang menampilkan keindahan budaya, pariwisata, dan potensi ekonomi Indonesia.',
                'servers' => [
                    [
                        'name' => 'Server 1 (Official OTT HLS)',
                        'type' => 'hls',
                        'url' => 'https://ott-balancer.tvri.go.id/live/eds/World/hls/World.m3u8',
                        'quality' => '1080p Full HD'
                    ]
                ],
                'is_featured' => false,
            ],
            [
                'id' => 'cnn-indonesia',
                'name' => 'CNN Indonesia',
                'slug' => 'cnn-indonesia',
                'category' => 'Berita',
                'tagline' => 'Kanal Berita Terpercaya',
                'logo' => '/icons/tv/cnn-indonesia.png',
                'description' => 'Jaringan berita internasional berbahasa Indonesia dengan liputan breaking news terdepan.',
                'servers' => [
                    [
                        'name' => 'Server 1 (HLS Direct)',
                        'type' => 'hls',
                        'url' => 'https://live.cnnindonesia.com/livecnn/smil:cnntv.smil/playlist.m3u8',
                        'quality' => '720p HD'
                    ],
                    [
                        'name' => 'Server 2 (YouTube Live)',
                        'type' => 'youtube',
                        'url' => 'https://www.youtube-nocookie.com/embed/PDDOkUq33Sw?autoplay=1&mute=0',
                        'video_id' => 'PDDOkUq33Sw',
                        'quality' => '1080p HD'
                    ]
                ],
                'is_featured' => true,
            ],
            [
                'id' => 'cnbc-indonesia',
                'name' => 'CNBC Indonesia',
                'slug' => 'cnbc-indonesia',
                'category' => 'Berita',
                'tagline' => 'Beyond Business',
                'logo' => '/icons/tv/cnbc-indonesia.png',
                'description' => 'Saluran TV berita ekonomi, bisnis, pasar saham, dan investasi terkemuka di Indonesia.',
                'servers' => [
                    [
                        'name' => 'Server 1 (HLS Direct)',
                        'type' => 'hls',
                        'url' => 'https://live.cnbcindonesia.com/livecnbc/smil:cnbctv.smil/playlist.m3u8',
                        'quality' => '720p HD'
                    ]
                ],
                'is_featured' => false,
            ],
            [
                'id' => 'btv',
                'name' => 'BTV (BeritaSatu)',
                'slug' => 'btv',
                'category' => 'Berita',
                'tagline' => 'Bersatu Menginspirasi',
                'logo' => '/icons/tv/btv.png',
                'description' => 'Saluran televisi berita dan gaya hidup yang menghadirkan program informatif dan inspiratif.',
                'servers' => [
                    [
                        'name' => 'Server 1 (YouTube Live 1080p)',
                        'type' => 'youtube',
                        'url' => 'https://www.youtube-nocookie.com/embed/wBno1XBcHYE?autoplay=1&mute=0',
                        'video_id' => 'wBno1XBcHYE',
                        'quality' => '1080p HD'
                    ],
                    [
                        'name' => 'Server 2 (HLS Direct)',
                        'type' => 'hls',
                        'url' => 'https://b1news.beritasatumedia.com/Beritasatu/B1News_manifest.m3u8',
                        'quality' => '720p HD'
                    ]
                ],
                'is_featured' => false,
            ],
            [
                'id' => 'inews',
                'name' => 'iNews',
                'slug' => 'inews',
                'category' => 'Berita',
                'tagline' => 'Inspiring & Informative',
                'logo' => '/icons/tv/inews.png',
                'description' => 'Jaringan televisi berita nasional dan daerah terbesar di Indonesia dari MNC Group.',
                'servers' => [
                    [
                        'name' => 'Server 1 (YouTube Live 1080p)',
                        'type' => 'youtube',
                        'url' => 'https://www.youtube-nocookie.com/embed/O_GQflKIY40?autoplay=1&mute=0',
                        'video_id' => 'O_GQflKIY40',
                        'quality' => '1080p HD'
                    ]
                ],
                'is_featured' => false,
            ],
            [
                'id' => 'seatoday',
                'name' => 'SEA Today',
                'slug' => 'seatoday',
                'category' => 'Berita',
                'tagline' => 'Southeast Asia Today',
                'logo' => '/icons/tv/seatoday.png',
                'description' => 'Saluran berita internasional 24 jam berbahasa Inggris dari Telkom Indonesia berfokus pada Asia Tenggara.',
                'servers' => [
                    [
                        'name' => 'Server 1 (YouTube Live 1080p)',
                        'type' => 'youtube',
                        'url' => 'https://www.youtube-nocookie.com/embed/vQPvTDN1Dnw?autoplay=1&mute=0',
                        'video_id' => 'vQPvTDN1Dnw',
                        'quality' => '1080p 60fps'
                    ]
                ],
                'is_featured' => false,
            ],
            [
                'id' => 'indonesiana',
                'name' => 'Indonesiana TV',
                'slug' => 'indonesiana',
                'category' => 'Budaya & Edukasi',
                'tagline' => 'Jendela Kebudayaan Indonesia',
                'logo' => '/icons/tv/indonesiana.png',
                'description' => 'Saluran kebudayaan resmi Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi RI.',
                'servers' => [
                    [
                        'name' => 'Server 1 (Cloudfront HLS)',
                        'type' => 'hls',
                        'url' => 'https://dgwubfppws111.cloudfront.net/out/v1/667a86e35ddd496c886fa11598dc184d/index.m3u8',
                        'quality' => '1080p Full HD'
                    ]
                ],
                'is_featured' => false,
            ],
            [
                'id' => 'sctv',
                'name' => 'SCTV',
                'slug' => 'sctv',
                'category' => 'Nasional',
                'tagline' => 'Satu Untuk Semua',
                'logo' => '/icons/tv/sctv.png',
                'description' => 'Saluran hiburan sinetron, FTV, Liga Champions, dan berita Liputan 6 dari Emtek.',
                'servers' => [
                    [
                        'name' => 'Server 1 (Web Mirror)',
                        'type' => 'iframe',
                        'url' => 'https://www.vidio.com/live/204-sctv',
                        'quality' => 'Adaptive'
                    ],
                    [
                        'name' => 'Server 2 (HLS Stream)',
                        'type' => 'hls',
                        'url' => 'https://op-group1-swiftservehd-1.dens.tv/h/h217/02.m3u8',
                        'quality' => '720p HD'
                    ]
                ],
                'is_featured' => true,
            ],
            [
                'id' => 'indosiar',
                'name' => 'Indosiar',
                'slug' => 'indosiar',
                'category' => 'Nasional',
                'tagline' => 'Luar Biasa',
                'logo' => '/icons/tv/indosiar.png',
                'description' => 'Saluran hiburan dangdut, drama mega series, siaran langsung sepak bola Liga 1 Indonesia.',
                'servers' => [
                    [
                        'name' => 'Server 1 (Web Mirror)',
                        'type' => 'iframe',
                        'url' => 'https://www.vidio.com/live/205-indosiar',
                        'quality' => 'Adaptive'
                    ],
                    [
                        'name' => 'Server 2 (HLS Stream)',
                        'type' => 'hls',
                        'url' => 'https://op-group1-swiftservehd-1.dens.tv/h/h235/02.m3u8',
                        'quality' => '720p HD'
                    ]
                ],
                'is_featured' => true,
            ],
            [
                'id' => 'rcti',
                'name' => 'RCTI',
                'slug' => 'rcti',
                'category' => 'Nasional',
                'tagline' => 'Kebanggaan Bersama Milik Bangsa',
                'logo' => '/icons/tv/rcti.png',
                'description' => 'Stasiun televisi swasta pertama di Indonesia dengan tayangan sinetron unggulan dan ajang pencarian bakat.',
                'servers' => [
                    [
                        'name' => 'Server 1 (HLS Live)',
                        'type' => 'hls',
                        'url' => 'https://proxy-hw.rctiplus.id/live/mweb/hdone/rplus1.m3u8',
                        'quality' => '720p HD'
                    ],
                    [
                        'name' => 'Server 2 (Web Mirror)',
                        'type' => 'iframe',
                        'url' => 'https://www.rctiplus.com/tv/rcti',
                        'quality' => 'Adaptive'
                    ]
                ],
                'is_featured' => true,
            ],
            [
                'id' => 'antv',
                'name' => 'ANTV',
                'slug' => 'antv',
                'category' => 'Nasional',
                'tagline' => 'Lebih Berwarna',
                'logo' => '/icons/tv/antv.png',
                'description' => 'Saluran hiburan serial drama favorit, animasi anak, dan program komedi keluarga.',
                'servers' => [
                    [
                        'name' => 'Server 1 (HLS Direct)',
                        'type' => 'hls',
                        'url' => 'http://103.58.160.157:8278/720-ANTV/playlist.m3u8',
                        'quality' => '720p HD'
                    ]
                ],
                'is_featured' => false,
            ]
        ];
    }

    /**
     * Dapatkan semua channel
     */
    /**
     * Cache metadata seluruh negara dari TV Garden
     */
    protected static ?array $countriesMetaCache = null;

    public static function getCountriesMetadata(): array
    {
        if (self::$countriesMetaCache !== null) {
            return self::$countriesMetaCache;
        }

        $file = storage_path('app/tv_garden/countries_metadata.json');
        if (!file_exists($file)) {
            $dir = dirname($file);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $raw = @file_get_contents('https://tvgarden.world/api/tv/countries_metadata.json');
            if ($raw) {
                if (substr($raw, 0, 2) === "\x1f\x8b") {
                    $raw = gzdecode($raw);
                }
                file_put_contents($file, $raw);
            }
        }

        if (file_exists($file)) {
            $raw = file_get_contents($file);
            if (substr($raw, 0, 2) === "\x1f\x8b") {
                $raw = gzdecode($raw);
            }
            self::$countriesMetaCache = json_decode($raw, true) ?: [];
        } else {
            self::$countriesMetaCache = [];
        }

        return self::$countriesMetaCache;
    }

    /**
     * Dapatkan daftar negara yang memiliki channel TV aktif
     */
    public static function getCountries(): array
    {
        $popularCodes = ['id', 'jp', 'kr', 'sa', 'us', 'uk', 'my', 'sg', 'th', 'tr', 'de', 'fr'];
        $meta = self::getCountriesMetadata();

        $popular = [];
        foreach ($popularCodes as $code) {
            $upper = strtoupper($code);
            $count = $meta[$upper]['channelCount'] ?? 0;
            if ($code === 'id') {
                $count += count(self::getCuratedChannels());
            }
            $popular[] = [
                'code' => $code,
                'name' => self::getCountryName($code),
                'flag' => self::getCountryFlag($code),
                'channel_count' => $count,
                'is_popular' => true,
            ];
        }

        $others = [];
        foreach ($meta as $code => $info) {
            $lower = strtolower($code);
            if (in_array($lower, $popularCodes)) continue;
            if (empty($info['hasChannels']) || empty($info['channelCount'])) continue;

            $others[] = [
                'code' => $lower,
                'name' => self::getCountryName($lower),
                'flag' => self::getCountryFlag($lower),
                'channel_count' => (int)$info['channelCount'],
                'is_popular' => false,
            ];
        }

        usort($others, fn($a, $b) => strcmp($a['name'], $b['name']));

        return array_merge($popular, $others);
    }

    public static function getCountryInfo(string $code): array
    {
        $code = strtolower(trim($code ?: 'id'));
        $meta = self::getCountriesMetadata();
        $upper = strtoupper($code);
        $count = $meta[$upper]['channelCount'] ?? 0;
        if ($code === 'id') {
            $count += count(self::getCuratedChannels());
        }

        return [
            'code' => $code,
            'name' => self::getCountryName($code),
            'flag' => self::getCountryFlag($code),
            'channel_count' => $count,
            'capital' => $meta[$upper]['capital'] ?? null,
            'time_zone' => $meta[$upper]['timeZone'] ?? null,
        ];
    }

    public static function getCountryFlag(string $code): string
    {
        $code = strtoupper(trim($code));
        if ($code === 'UK') $code = 'GB';
        if (strlen($code) !== 2) return '🌐';
        return mb_chr(ord($code[0]) - ord('A') + 0x1F1E6) . mb_chr(ord($code[1]) - ord('A') + 0x1F1E6);
    }

    public static function getCountryName(string $code): string
    {
        $code = strtolower(trim($code));
        $names = [
            'id' => 'Indonesia',
            'jp' => 'Jepang',
            'kr' => 'Korea Selatan',
            'sa' => 'Arab Saudi',
            'us' => 'Amerika Serikat',
            'uk' => 'Inggris (UK)',
            'my' => 'Malaysia',
            'sg' => 'Singapura',
            'th' => 'Thailand',
            'tr' => 'Turki',
            'de' => 'Jerman',
            'fr' => 'Prancis',
            'it' => 'Italia',
            'es' => 'Spanyol',
            'au' => 'Australia',
            'ca' => 'Kanada',
            'br' => 'Brasil',
            'ru' => 'Rusia',
            'cn' => 'China',
            'in' => 'India',
            'nl' => 'Belanda',
            'ph' => 'Filipina',
            'vn' => 'Vietnam',
            'ae' => 'Uni Emirat Arab',
            'eg' => 'Mesir',
            'ps' => 'Palestina',
        ];

        if (isset($names[$code])) return $names[$code];

        $meta = self::getCountriesMetadata();
        $upper = strtoupper($code);
        return $meta[$upper]['country'] ?? strtoupper($code);
    }

    /**
     * Dapatkan daftar channel dari TV Garden untuk negara tertentu
     */
    public static function getTvGardenChannels(string $country = 'id'): array
    {
        $country = strtolower(trim($country ?: 'id'));
        $cacheFile = storage_path('app/tv_garden/countries/' . $country . '.json');

        if (!file_exists($cacheFile)) {
            $dir = dirname($cacheFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $url = 'https://tvgarden.world/api/tv/countries/' . $country . '.json';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $raw = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && !empty($raw)) {
                if (substr($raw, 0, 2) === "\x1f\x8b") {
                    $raw = gzdecode($raw);
                }
                file_put_contents($cacheFile, $raw);
            } else {
                return [];
            }
        }

        $raw = file_get_contents($cacheFile);
        if (substr($raw, 0, 2) === "\x1f\x8b") {
            $raw = gzdecode($raw);
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }

        $flag = self::getCountryFlag($country);
        $countryName = self::getCountryName($country);
        $channels = [];

        foreach ($data as $item) {
            $nanoid = $item['nanoid'] ?? null;
            if (!$nanoid) continue;

            $name = trim($item['name'] ?? '[Tanpa Nama]');
            $streamUrls = is_array($item['stream_urls'] ?? null) ? array_values(array_filter($item['stream_urls'])) : [];
            $youtubeUrls = is_array($item['youtube_urls'] ?? null) ? array_values(array_filter($item['youtube_urls'])) : [];

            if (empty($streamUrls) && empty($youtubeUrls)) {
                continue;
            }

            $servers = [];
            $srvIdx = 1;
            foreach ($streamUrls as $sUrl) {
                $servers[] = [
                    'name' => 'Server ' . $srvIdx++ . ' (HLS Direct)',
                    'type' => 'hls',
                    'url' => $sUrl,
                    'quality' => 'HD'
                ];
            }
            foreach ($youtubeUrls as $yUrl) {
                $embedUrl = $yUrl;
                if (!str_contains($embedUrl, 'origin=')) {
                    $sep = str_contains($embedUrl, '?') ? '&' : '?';
                    $embedUrl .= $sep . 'autoplay=1&mute=0&playsinline=1&origin=https://streaming.kazeview.com';
                }
                $servers[] = [
                    'name' => 'Server ' . $srvIdx++ . ' (YouTube Live)',
                    'type' => 'youtube',
                    'url' => $embedUrl,
                    'quality' => '1080p'
                ];
            }

            $category = self::inferCategory($name, $country);
            $slug = 'tvg-' . $nanoid;

            $channels[] = [
                'id' => $slug,
                'name' => $name,
                'slug' => $slug,
                'nanoid' => $nanoid,
                'country' => $country,
                'country_name' => $countryName,
                'flag' => $flag,
                'category' => $category,
                'tagline' => 'Siaran TV ' . $name . ' (' . $countryName . ')',
                'logo' => null,
                'description' => 'Siaran langsung televisi ' . $name . ' asal negara ' . $countryName . ' tanpa jeda buffering.',
                'servers' => $servers,
                'is_featured' => false,
                'is_geo_blocked' => (bool)($item['isGeoBlocked'] ?? false),
                'source' => 'tvgarden'
            ];
        }

        return $channels;
    }

    /**
     * Klasifikasikan kategori secara cerdas
     */
    protected static function inferCategory(string $name, string $country): string
    {
        $nameLower = strtolower($name);
        if ($country === 'id') {
            if (str_contains($nameLower, 'tvri')) {
                return 'Daerah & TVRI';
            }
            if (str_contains($nameLower, 'quran') || str_contains($nameLower, 'rodja') || str_contains($nameLower, 'madani') || str_contains($nameLower, 'ashiil') || str_contains($nameLower, 'islam') || str_contains($nameLower, 'tarim') || str_contains($nameLower, 'al wafa') || str_contains($nameLower, 'sunnah')) {
                return 'Religi & Islami';
            }
            if (str_contains($nameLower, 'musik') || str_contains($nameLower, 'music') || str_contains($nameLower, 'frekuensi')) {
                return 'Musik & Hiburan';
            }
            if (str_contains($nameLower, 'news') || str_contains($nameLower, 'berita')) {
                return 'Berita & Bisnis';
            }
            return 'Daerah & Komunitas';
        }

        if (str_contains($nameLower, 'news') || str_contains($nameLower, 'berita') || str_contains($nameLower, 'alert') || str_contains($nameLower, 'cnn') || str_contains($nameLower, 'cna') || str_contains($nameLower, 'weather')) {
            return 'Berita & Informasi';
        }
        if (str_contains($nameLower, 'music') || str_contains($nameLower, 'musik') || str_contains($nameLower, 'k-pop') || str_contains($nameLower, 'pop')) {
            return 'Musik & Hiburan';
        }
        if (str_contains($nameLower, 'quran') || str_contains($nameLower, 'islam') || str_contains($nameLower, 'church') || str_contains($nameLower, 'gospel') || str_contains($nameLower, 'pray')) {
            return 'Religi';
        }
        if (str_contains($nameLower, 'kids') || str_contains($nameLower, 'kartun') || str_contains($nameLower, 'cartoon') || str_contains($nameLower, 'anime') || str_contains($nameLower, 'anileap')) {
            return 'Animasi & Anak';
        }
        if (str_contains($nameLower, 'sport') || str_contains($nameLower, 'bola')) {
            return 'Olahraga';
        }

        return 'Umum';
    }

    /**
     * Dapatkan semua channel (Hybrid untuk ID, TV Garden untuk negara lain)
     */
    public static function getChannels(string $country = 'id', ?string $category = null, ?string $search = null): array
    {
        $country = strtolower(trim($country ?: 'id'));
        $channels = [];

        if ($country === 'id') {
            $curated = self::getCuratedChannels();
            $tvg = self::getTvGardenChannels('id');

            $curatedNamesMap = [
                'kompas tv' => true,
                'metro tv' => true,
                'tvone' => true,
                'tvonenews' => true,
                'tvri' => true,
                'tvri nasional' => true,
                'trans7' => true,
                'trans tv' => true,
                'sctv' => true,
                'indosiar' => true,
                'rcti' => true,
                'antv' => true,
                'rtv' => true,
                'rajawali tv' => true,
                'inews' => true,
                'tvri sport' => true,
                'tvri world' => true,
                'cnn indonesia' => true,
                'cnbc indonesia' => true,
                'btv' => true,
                'sea today' => true,
                'indonesiana tv' => true,
                'indonesiana.tv' => true,
            ];

            $filteredTvg = [];
            foreach ($tvg as $t) {
                $n = strtolower(trim($t['name']));
                if (isset($curatedNamesMap[$n])) {
                    continue;
                }
                $filteredTvg[] = $t;
            }

            $channels = array_merge($curated, $filteredTvg);
        } else {
            $channels = self::getTvGardenChannels($country);
        }

        if (!empty($category) && $category !== 'all') {
            $channels = array_values(array_filter($channels, function ($ch) use ($category) {
                return strtolower($ch['category']) === strtolower($category);
            }));
        }

        if (!empty($search)) {
            $keyword = strtolower(trim($search));
            $channels = array_values(array_filter($channels, function ($ch) use ($keyword) {
                return str_contains(strtolower($ch['name']), $keyword)
                    || str_contains(strtolower($ch['description'] ?? ''), $keyword)
                    || str_contains(strtolower($ch['category'] ?? ''), $keyword);
            }));
        }

        return $channels;
    }

    public static function getAll(string $country = 'id'): array
    {
        return self::getChannels($country);
    }

    public static function getCategories(string $country = 'id'): array
    {
        $channels = self::getChannels($country);
        $categories = ['all'];

        foreach ($channels as $ch) {
            if (!empty($ch['category']) && !in_array($ch['category'], $categories)) {
                $categories[] = $ch['category'];
            }
        }

        return $categories;
    }

    public static function findBySlug(string $slug, ?string $country = null): ?array
    {
        // 1. Cek channel kurasi nasional
        foreach (self::getCuratedChannels() as $channel) {
            if ($channel['slug'] === $slug || $channel['id'] === $slug) {
                return $channel;
            }
        }

        // 2. Jika ada parameter negara, cari di negara tersebut
        if ($country) {
            foreach (self::getChannels($country) as $channel) {
                if ($channel['slug'] === $slug || $channel['id'] === $slug || ($channel['nanoid'] ?? null) === $slug) {
                    return $channel;
                }
            }
        }

        // 3. Cek channel Indonesia
        foreach (self::getChannels('id') as $channel) {
            if ($channel['slug'] === $slug || $channel['id'] === $slug || ($channel['nanoid'] ?? null) === $slug) {
                return $channel;
            }
        }

        // 4. Jika slug tvg-*, cari di cache negara populer
        if (str_starts_with($slug, 'tvg-')) {
            $nanoid = substr($slug, 4);
            $popular = ['jp', 'kr', 'sa', 'us', 'uk', 'my', 'sg', 'de', 'fr', 'tr', 'th'];
            foreach ($popular as $c) {
                foreach (self::getChannels($c) as $channel) {
                    if ($channel['slug'] === $slug || ($channel['nanoid'] ?? null) === $nanoid) {
                        return $channel;
                    }
                }
            }
        }

        return null;
    }
}
