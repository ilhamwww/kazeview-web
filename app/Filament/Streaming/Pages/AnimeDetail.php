<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimeDownloadResolverService;
use App\Services\AnimeMetadataService;
use App\Services\MirainimeScraperService;
use App\Services\OtakudesuScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class AnimeDetail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Anime Detail';
    protected static ?string $title = 'Anime Detail';
    protected static ?string $slug = 'anime/{slug}';
    protected static string $view = 'filament.streaming.pages.anime-detail-v1';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public ?array $detail = null;
    public ?array $metadata = null;
    public ?array $downloads = null;
    public string $selectedDownloadEp = '1';
    public bool $isFavorited = false;

    public function mount($slug): void
    {
        $slug = preg_replace('/^(?:anime_v\d+:|v\d+:|anime:)/', '', $slug);
        $this->animeSlug = $slug;

        $scraper = new MirainimeScraperService();
        $cacheKey = 'v1_detail_' . md5($slug);
        $this->detail = Cache::remember($cacheKey, 43200, function () use ($scraper, $slug) {
            $data = $scraper->scrapeAnimeSeriesDetail($slug);
            if ($data && (($data['title'] ?? '') === '404' || empty($data['episodes']))) {
                return null;
            }
            return $data;
        });

        // Fallback jika tidak ditemukan atau 404 di Mirainime, ambil dari Otakudesu
        if (!$this->detail || ($this->detail['title'] ?? '') === '404' || empty($this->detail['episodes'])) {
            $otakuScraper = new OtakudesuScraperService();
            $otakuUrl = 'https://otakudesu.blog/anime/' . $slug . '/';
            $otakuDetail = Cache::remember('otaku_series_' . md5($slug), 43200, function () use ($otakuScraper, $otakuUrl) {
                return $otakuScraper->scrapeAnimeSeriesDetail($otakuUrl);
            });
            if ($otakuDetail && !empty($otakuDetail['title'])) {
                // Cek apakah Mirainime punya judul ini tapi dengan slug berbeda (misal: tempal-chikara vs temppal-item-no-chikara)
                $cleanTitle = AnimeMetadataService::cleanTitle($otakuDetail['title']);
                $miraiMatches = $scraper->searchAnime($cleanTitle);
                if (!empty($miraiMatches[0]['slug'])) {
                    $matchedMiraiSlug = $miraiMatches[0]['slug'];
                    $miraiDetail = $scraper->scrapeAnimeSeriesDetail($matchedMiraiSlug);
                    if ($miraiDetail && !empty($miraiDetail['episodes']) && ($miraiDetail['title'] ?? '') !== '404') {
                        $this->detail = $miraiDetail;
                        $this->animeSlug = $matchedMiraiSlug;
                    }
                }

                if (!$this->detail) {
                    $rawEpisodes = $otakuDetail['episodes'] ?? [];
                    usort($rawEpisodes, function ($a, $b) {
                        $ea = (int)preg_replace('/[^\d]/', '', (string)($a['episode'] ?? '0'));
                        $eb = (int)preg_replace('/[^\d]/', '', (string)($b['episode'] ?? '0'));
                        return $ea <=> $eb;
                    });

                    $this->detail = [
                        'title' => $otakuDetail['title'] ?? '',
                        'japanese_title' => $otakuDetail['japanese_title'] ?? null,
                        'english_title' => null,
                        'score' => $otakuDetail['score'] ?? null,
                        'status' => $otakuDetail['status'] ?? 'Ongoing',
                        'studio' => $otakuDetail['studio'] ?? null,
                        'season' => null,
                        'duration' => $otakuDetail['duration'] ?? null,
                        'broadcast_day' => null,
                        'next_episode_schedule' => null,
                        'thumbnail' => $otakuDetail['thumbnail'] ?? ($otakuDetail['poster'] ?? ''),
                        'banner' => $otakuDetail['thumbnail'] ?? ($otakuDetail['poster'] ?? ''),
                        'description' => $otakuDetail['synopsis'] ?? '',
                        'genres' => $otakuDetail['genres'] ?? [],
                        'episodes' => array_map(function ($ep) {
                            return [
                                'episode' => (string)($ep['episode'] ?? '1'),
                                'title' => $ep['title'] ?? ('Episode ' . ($ep['episode'] ?? '1')),
                                'url' => $ep['url'] ?? '',
                            ];
                        }, $rawEpisodes),
                        'is_otakudesu' => true,
                    ];
                }
            }
        }

        if (!$this->detail || ($this->detail['title'] ?? '') === '404') {
            abort(404, 'Detail anime tidak ditemukan');
        }

        $title = $this->detail['title'] ?? '';

        // 1. Metadata langsung dari Mirainime (atau Otakudesu saat fallback) — Zero External API latency
        $this->metadata = [
            'title' => $this->detail['title'] ?? '',
            'english_title' => $this->detail['english_title'] ?? null,
            'native_title' => $this->detail['japanese_title'] ?? null,
            'score' => $this->detail['score'] ?? null,
            'status' => $this->detail['status'] ?? 'Ongoing',
            'studio' => $this->detail['studio'] ?? null,
            'season' => $this->detail['season'] ?? null,
            'duration' => $this->detail['duration'] ?? null,
            'broadcast_day' => $this->detail['broadcast_day'] ?? null,
            'next_episode_schedule' => $this->detail['next_episode_schedule'] ?? null,
            'genres' => $this->detail['genres'] ?? [],
            'synopsis' => $this->detail['description'] ?? '',
            'poster' => $this->detail['thumbnail'] ?? null,
            'banner' => $this->detail['banner'] ?? ($this->detail['thumbnail'] ?? null),
        ];

        // 2. Ambil Opsi Download Resolusi Otakudesu (Episode 1 & Batch)
        $firstEp = !empty($this->detail['episodes'][0]['episode']) ? (string) $this->detail['episodes'][0]['episode'] : '1';
        $this->selectedDownloadEp = $firstEp;
        $this->downloads = AnimeDownloadResolverService::getDownloads($title, $this->selectedDownloadEp);

        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', 'v1:' . $this->animeSlug)
                ->where('item_type', 'anime')
                ->exists()
            : false;
    }

    public function selectDownloadEpisode(string $ep): void
    {
        $this->selectedDownloadEp = $ep;
        $title = $this->detail['title'] ?? '';
        $this->downloads = AnimeDownloadResolverService::getDownloads($title, $ep);
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $fav = $user->favorites()
            ->where('item_id', 'v1:' . $this->animeSlug)
            ->where('item_type', 'anime')
            ->first();

        if ($fav) {
            $fav->delete();
            $this->isFavorited = false;
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Favorit')
                ->success()
                ->send();
        } else {
            $user->favorites()->create([
                'item_id' => 'v1:' . $this->animeSlug,
                'item_type' => 'anime',
                'title' => $this->detail['title'] ?? 'Anime Detail',
                'thumbnail' => $this->detail['thumbnail'] ?? null,
                'url' => request()->url(),
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Favorit')
                ->success()
                ->send();
        }
    }
}
