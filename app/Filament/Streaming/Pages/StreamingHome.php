<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Services\StreamingService;
use App\Filament\Streaming\Pages\KoreanDrama;
use Illuminate\Support\Facades\Log;

class StreamingHome extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Home';
    protected static ?string $title = 'Streaming Home';
    protected static ?string $slug = '';
    protected static string $view = 'filament.streaming.pages.streaming-home';
    protected static string $layout = 'filament.streaming.layout';

    public array $heroes = [];
    public array $sections = [];
    public array $topTen = [];
    public array $continueWatching = [];
    public array $quickLinks = [];

    /**
     * Urutan & label section Home. Section yang tidak terdaftar tetap tampil di akhir.
     * style: poster (default) | spotlight (kartu lebar)
     */
    private const SECTION_META = [
        'tmdb-trending'     => ['title' => 'Sorotan Minggu Ini', 'style' => 'spotlight'],
        'latest-movies'     => ['title' => 'Film Terbaru', 'more' => 'movies'],
        'latest-series'     => ['title' => 'Series Terbaru', 'more' => 'series'],
        'indonesia'         => ['title' => 'Film & Series Indonesia', 'more' => 'indonesia'],
        'drakor'            => ['title' => 'Drakor', 'more' => 'drakor'],
        'collections'       => ['title' => 'Dari Koleksi Pilihan', 'more' => 'collections'],
    ];

    public function mount(): void
    {
        $homepageData = StreamingService::getHomepageData();

        $heroes = $homepageData['heroes'] ?? [];
        $sections = $homepageData['sections'] ?? [];

        // Fallback ke dummy data kalau API gagal / kosong
        if (empty($sections) && empty($heroes)) {
            $dummy = StreamingService::getDummyData();
            $heroes = array_slice($dummy, 0, 5);
            $sections = [['title' => 'Film Terbaru', 'slug' => 'latest-movies', 'items' => $dummy]];
        }

        $this->heroes = array_slice(array_values(array_filter(
            array_map(fn ($i) => $this->withUrl($i), $heroes),
            fn ($i) => $i['url'] !== '#'
        )), 0, 8);
        $this->buildSections($sections);
        $this->quickLinks = $this->buildQuickLinks();
        $this->continueWatching = $this->buildContinueWatching();
    }

    private function buildSections(array $sections): void
    {
        $bySlug = [];
        $trendingPool = [];
        foreach ($sections as $section) {
            $slug = $section['slug'] ?? '';

            // Hanya item yang benar-benar bisa dibuka (punya slug). Ini membuang placeholder
            // seperti "Recently Added Episodes" yang tidak punya link/gambar asli.
            $items = array_values(array_filter(
                array_map(fn ($i) => $this->withUrl($i), $section['items'] ?? []),
                fn ($i) => $i['url'] !== '#'
            ));

            if (empty($items)) {
                continue;
            }

            if ($slug === 'trending-now') {
                $this->topTen = array_slice($items, 0, 10);
                continue;
            }

            // Chitogenx: rak trending film & serial dipakai untuk Top 10.
            if (in_array($slug, ['film-yang-sedang-tren', 'drama-yang-sedang-trending'], true)) {
                $trendingPool[$slug] = $items;
            }

            $meta = self::SECTION_META[$slug] ?? [];
            $title = $meta['title'] ?? null;
            if (!$title) {
                $title = str_replace('Trending in', 'Trending di', $section['title'] ?? '') ?: 'Pilihan Untukmu';
            }

            $bySlug[$slug] = [
                'slug' => $slug,
                'title' => $title,
                'style' => $meta['style'] ?? 'poster',
                'more_url' => $this->moreUrl($meta['more'] ?? null),
                'items' => $items,
            ];
        }

        $ordered = [];
        foreach (array_keys(self::SECTION_META) as $slug) {
            if (isset($bySlug[$slug])) {
                $ordered[] = $bySlug[$slug];
                unset($bySlug[$slug]);
            }
        }
        $this->sections = array_merge($ordered, array_values($bySlug));

        if (empty($this->topTen) && !empty($trendingPool)) {
            // Selang-seling film dan serial supaya Top 10 tidak didominasi satu jenis.
            $films = $trendingPool['film-yang-sedang-tren'] ?? [];
            $series = $trendingPool['drama-yang-sedang-trending'] ?? [];
            $mixed = [];
            $seen = [];

            for ($i = 0; count($mixed) < 10 && ($i < count($films) || $i < count($series)); $i++) {
                foreach ([$series[$i] ?? null, $films[$i] ?? null] as $item) {
                    if ($item && !isset($seen[$item['slug']]) && count($mixed) < 10) {
                        $seen[$item['slug']] = true;
                        $mixed[] = $item;
                    }
                }
            }

            $this->topTen = $mixed;
        }
    }

    private function withUrl(array $item): array
    {
        $isSeries = in_array($item['type'] ?? '', ['tv_series', 'tv', 'series'], true);
        $item['is_series'] = $isSeries;
        $item['url'] = '#';

        if (!empty($item['slug'])) {
            $item['url'] = $isSeries
                ? ShowSeries::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming')
                : WatchMovie::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming');
        }

        // Rating 0.0 artinya belum ada rating, jangan ditampilkan
        if (isset($item['rating']) && (float) $item['rating'] <= 0) {
            $item['rating'] = null;
        }

        return $item;
    }

    private function moreUrl(?string $key): ?string
    {
        return match ($key) {
            'movies' => Movies::getUrl(panel: 'streaming'),
            'series' => TVSeries::getUrl(panel: 'streaming'),
            'indonesia' => IndonesiaHub::getUrl(panel: 'streaming'),
            'drakor' => KoreanDrama::getUrl(panel: 'streaming'),
            'collections' => Collections::getUrl(panel: 'streaming'),
            default => null,
        };
    }


    private function buildQuickLinks(): array
    {
        return [
            ['label' => 'Movies', 'url' => Movies::getUrl(panel: 'streaming'), 'icon' => 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z'],
            ['label' => 'TV Series', 'url' => TVSeries::getUrl(panel: 'streaming'), 'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['label' => 'Indonesia', 'url' => IndonesiaHub::getUrl(panel: 'streaming'), 'icon' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3'],
            ['label' => 'Drakor', 'url' => KoreanDrama::getUrl(panel: 'streaming'), 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Koleksi', 'url' => Collections::getUrl(panel: 'streaming'), 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
            ['label' => 'Dracin', 'url' => Dracin::getUrl(panel: 'streaming'), 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
            ['label' => 'Anime', 'url' => ExploreAnime::getUrl(panel: 'streaming'), 'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Watchlist', 'url' => Watchlist::getUrl(panel: 'streaming'), 'icon' => 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z'],
        ];
    }

    /**
     * Riwayat tontonan user. URL lama dari Dracin/Bstation tersimpan sebagai /livewire/update,
     * jadi link-nya dibangun ulang dari item_id.
     */
    private function buildContinueWatching(): array
    {
        if (!auth()->check()) {
            return [];
        }

        try {
            $rows = auth()->user()->watchHistories()
                ->where('item_id', 'not like', 'lk:%')
                ->latest('updated_at')
                ->limit(15)
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Home continue watching gagal: ' . $e->getMessage());
            return [];
        }

        $labels = ['dracin' => 'Dracin', 'anime' => 'Anime', 'bstation' => 'Bstation', 'movie' => 'Film', 'series' => 'Series'];
        $result = [];
        $animeV5Rows = $rows->filter(fn ($row) => $row->item_type === 'anime' && str_starts_with((string) $row->item_id, 'anime_v5:'));
        foreach ($animeV5Rows as $row) {
            $slug = preg_replace('/^anime_v5:/', '', (string) $row->item_id);
            $episode = (string) ($row->last_episode ?: '1');
            $result[] = [
                'title' => $row->title ?: $slug,
                'thumbnail' => $row->thumbnail,
                'episode' => $episode,
                'label' => 'Anime',
                'url' => WatchAnimeV5::getUrl(['anime_slug' => $slug, 'episode_slug' => $episode], panel: 'streaming'),
                'ago' => $row->updated_at ? $row->updated_at->locale('id')->diffForHumans() : null,
                'progress_seconds' => round($row->progress_seconds),
                'duration_seconds' => round($row->duration_seconds),
                'progress_percent' => round($row->progress_percent, 1),
                'progress_display' => $row->formatted_progress,
                'duration_display' => $row->formatted_duration,
                'has_progress' => $row->progress_seconds > 0 && $row->duration_seconds > 0,
            ];
        }
        $rows = $rows->reject(fn ($row) => $row->item_type === 'anime' && str_starts_with((string) $row->item_id, 'anime_v5:'));

        foreach ($rows as $row) {
            // Riwayat lama yang slug-nya tidak ada di provider aktif
            // dilewati supaya "Lanjutkan Menonton" tidak pernah mengarah ke halaman mati.
            if (in_array($row->item_type, ['indonesia_movie', 'indonesia_series'], true)) {
                // Rebahin/LK21 sudah dipensiunkan.
                continue;
            }

            if (in_array($row->item_type, ['movie', 'series'], true)) {
                $slug = (string) $row->item_id;
                $otherProvider = (bool) preg_match('/^(rb-|mb-|ix-)/', $slug);

                if (!$otherProvider && !\App\Services\ChitogenxService::hasSlug($slug)) {
                    continue;
                }
            }

            $url = null;
            try {
                if (!empty($row->url) && !str_contains($row->url, '/livewire/')) {
                    $url = preg_replace('#^https?://[^/]+(?:/streaming)?/#', '/', $row->url);
                    if (!str_starts_with($url, '/')) {
                        $url = '/' . $url;
                    }
                    $url = str_replace('/anime-v1/', '/anime/', $url);
                } elseif ($row->item_type === 'dracin' && str_contains($row->item_id, ':')) {
                    [$net, $id] = explode(':', $row->item_id, 2);
                    $url = DracinDetail::getUrl(parameters: ['network_slug' => $net, 'id' => $id], panel: 'streaming');
                } elseif ($row->item_type === 'bstation') {
                    $url = BstationDetail::getUrl(parameters: ['id' => $row->item_id], panel: 'streaming');
                } elseif ($row->item_type === 'movie') {
                    $url = url('/movie/' . $row->item_id);
                } elseif ($row->item_type === 'series') {
                    $ep = $row->last_episode ?: 1;
                    $url = url('/series/' . $row->item_id . '/season/1/episode/' . $ep);
                } elseif ($row->item_type === 'anime') {
                    $cleanSlug = preg_replace('/^(?:anime_v\d+:|v\d+:|anime:)/', '', $row->item_id);
                    $ep = $row->last_episode ?: '1';
                    if (str_starts_with($row->item_id, 'anime_v5:')) {
                        $url = WatchAnimeV5::getUrl(['anime_slug' => $cleanSlug, 'episode_slug' => (string) $ep], panel: 'streaming');
                    } else {
                        $url = url('/anime/' . $cleanSlug . '/episode/' . $ep);
                    }
                }
            } catch (\Throwable $e) {
                $url = null;
            }

            if (!$url) {
                continue;
            }

            $thumbnail = $row->thumbnail;
            if (empty($thumbnail)) {
                try {
                    if (in_array($row->item_type, ['tv_series', 'tv', 'series'], true)) {
                        $seriesDetail = StreamingService::getSeriesDetail($row->item_id);
                        $thumbnail = $seriesDetail['posterPath'] ?? ($seriesDetail['poster_path'] ?? ($seriesDetail['poster'] ?? ($seriesDetail['thumbnail'] ?? null)));
                    } elseif ($row->item_type === 'movie') {
                        $movieDetail = StreamingService::getMovieDetail($row->item_id);
                        $thumbnail = $movieDetail['thumbnail'] ?? ($movieDetail['poster'] ?? ($movieDetail['posterPath'] ?? ($movieDetail['poster_path'] ?? null)));
                    } elseif ($row->item_type === 'anime') {
                        $cleanSlug = preg_replace('/^(?:anime_v\d+:|v\d+:|anime:)/', '', $row->item_id);
                        $animeDetail = app(\App\Services\MirainimeScraperService::class)->getAnimeDetail($cleanSlug);
                        $thumbnail = $animeDetail['poster'] ?? null;
                    }
                    if (!empty($thumbnail)) {
                        $row->update(['thumbnail' => $thumbnail]);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            $result[] = [
                'title' => $row->title,
                'thumbnail' => $thumbnail,
                'episode' => $row->last_episode,
                'label' => $labels[$row->item_type] ?? ucfirst($row->item_type),
                'url' => $url,
                'ago' => $row->updated_at ? $row->updated_at->locale('id')->diffForHumans() : null,
                'progress_seconds' => round($row->progress_seconds),
                'duration_seconds' => round($row->duration_seconds),
                'progress_percent' => round($row->progress_percent, 1),
                'progress_display' => $row->formatted_progress,
                'duration_display' => $row->formatted_duration,
                'has_progress' => $row->progress_seconds > 0 && $row->duration_seconds > 0,
            ];
        }

        return array_slice($result, 0, 12);
    }
}
