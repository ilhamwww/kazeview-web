<?php

namespace App\Services;

class LiveTvService
{
    /**
     * Daftar stasiun TV Indonesia lengkap dengan metadata logo, kategori, dan multiple stream server.
     */
    public static function getChannels(): array
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
    public static function getAll(): array
    {
        return self::getChannels();
    }

    /**
     * Dapatkan daftar kategori yang tersedia
     */
    public static function getCategories(): array
    {
        $categories = array_unique(array_column(self::getChannels(), 'category'));
        return array_values($categories);
    }

    /**
     * Dapatkan channel berdasarkan slug / ID
     */
    public static function findBySlug(string $slug): ?array
    {
        $channels = self::getChannels();
        foreach ($channels as $channel) {
            if ($channel['slug'] === $slug || $channel['id'] === $slug) {
                return $channel;
            }
        }
        return null;
    }

    /**
     * Filter channel berdasarkan kategori
     */
    public static function getByCategory(string $category): array
    {
        if ($category === 'all' || empty($category)) {
            return self::getChannels();
        }

        return array_values(array_filter(self::getChannels(), function ($channel) use ($category) {
            return strtolower($channel['category']) === strtolower($category);
        }));
    }

    /**
     * Cari channel berdasarkan nama / tagline
     */
    public static function search(string $keyword): array
    {
        $keyword = strtolower(trim($keyword));
        if (empty($keyword)) {
            return self::getChannels();
        }

        return array_values(array_filter(self::getChannels(), function ($channel) use ($keyword) {
            return str_contains(strtolower($channel['name']), $keyword)
                || str_contains(strtolower($channel['description']), $keyword)
                || str_contains(strtolower($channel['category']), $keyword);
        }));
    }
}
