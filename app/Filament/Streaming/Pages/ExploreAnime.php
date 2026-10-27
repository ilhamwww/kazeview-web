<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimeMetadataService;
use App\Services\MirainimeScraperService;
use App\Services\OtakudesuScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ExploreAnime extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Anime';
    protected static ?string $title = 'Anime';
    protected static ?string $slug = 'anime';
    protected static string $view = 'filament.streaming.pages.explore-anime-v1';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $pageNumber = 1;
    public array $animes = [];
    public array $genres = [];
    public string $search = '';
    public string $genre = '';
    public string $status = '';
    public array $home = [];
    public array $recent = [];
    public array $updates = [];
    public array $weeklySchedule = [];
    public string $selectedScheduleDay = 'Senin';
    public string $currentDayName = 'Senin';

    protected function rememberCache(string $key, int $ttl, \Closure $callback)
    {
        $value = Cache::get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();

        if ($value !== null && (!is_array($value) || !empty($value))) {
            Cache::put($key, $value, $ttl);
        }

        return $value;
    }

    public function mount(): void
    {
        $this->genres = $this->rememberCache('v1_genres', 86400, function () {
            $scraper = new MirainimeScraperService();
            return $scraper->scrapeGenres();
        });

        // Beranda V1: episode terbaru, tren, musim ini (cache singkat supaya tetap fresh)
        $this->home = $this->rememberCache('v1_home', 600, function () {
            $data = (new MirainimeScraperService())->scrapeHomepage();
            return empty($data['latest']) && empty($data['hero']) ? [] : $data;
        }) ?? [];

        $this->recent = $this->rememberCache('v1_recent_feed', 600, function () {
            return (new MirainimeScraperService())->scrapeRecentFeed(24);
        }) ?? [];

        // Feed "Baru Update" berposter (prioritas utama halaman /anime)
        $this->updates = Cache::get('v1_updates_feed') ?? [];
        if (empty($this->updates)) {
            $this->updates = $this->buildUpdates();
            // Kalau ada poster yang gagal diambil (timeout upstream), cache singkat supaya cepat dicoba ulang
            $complete = !collect($this->updates)->contains(fn ($u) => empty($u['poster']));
            if (!empty($this->updates)) {
                Cache::put('v1_updates_feed', $this->updates, $complete ? 600 : 60);
            }
        }

        // Jadwal Tayang Mingguan dari Mirainime (Senin - Minggu)
        $dayMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $nowDay = (int) (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('N');
        $this->currentDayName = $dayMap[$nowDay] ?? 'Senin';
        $this->selectedScheduleDay = $this->currentDayName;
        $this->weeklySchedule = (new MirainimeScraperService())->scrapeWeeklySchedule();

        $this->loadAnimes();
    }

    /**
     * Gabungkan RSS episode terbaru + "latest" homepage jadi satu daftar per serial,
     * urut paling baru, lengkap dengan poster (yang kosong diambil paralel & di-cache).
     */
    protected function buildUpdates(): array
    {
        $posters = [];
        foreach (['latest', 'hero', 'trending', 'season', 'top'] as $key) {
            foreach ($this->home[$key] ?? [] as $it) {
                if (!empty($it['slug']) && !empty($it['poster']) && empty($posters[$it['slug']])) {
                    $posters[$it['slug']] = $it['poster'];
                }
            }
        }

        $meta = [];
        foreach ($this->home['latest'] ?? [] as $it) {
            if (!empty($it['slug'])) {
                $meta[$it['slug']] = $it;
            }
        }

        $isNew = function (?int $ts, string $ago): bool {
            if ($ts) {
                return $ts > time() - 86400;
            }
            return (bool) preg_match('/menit|jam|detik/i', $ago);
        };

        $items = [];
        foreach ($this->recent as $r) {
            $slug = $r['slug'] ?? '';
            if ($slug === '') {
                continue;
            }
            $m = $meta[$slug] ?? [];
            $items[$slug] = [
                'slug' => $slug,
                'title' => ($r['series'] ?? '') ?: ($m['series'] ?? $slug),
                'episode' => (string) ($r['episode'] ?? ''),
                'poster' => $posters[$slug] ?? '',
                'type' => $m['type'] ?? '',
                'rating' => $m['rating'] ?? '',
                'ago' => $r['ago'] ?? '',
                'is_new' => $isNew($r['timestamp'] ?? null, $r['ago'] ?? ''),
            ];
        }

        foreach ($this->home['latest'] ?? [] as $it) {
            $slug = $it['slug'] ?? '';
            if ($slug === '' || isset($items[$slug])) {
                continue;
            }
            $items[$slug] = [
                'slug' => $slug,
                'title' => ($it['series'] ?? '') ?: ($it['title'] ?? $slug),
                'episode' => (string) ($it['episode'] ?? ''),
                'poster' => $it['poster'] ?? '',
                'type' => $it['type'] ?? '',
                'rating' => $it['rating'] ?? '',
                'ago' => $it['ago'] ?? '',
                'is_new' => $isNew(null, $it['ago'] ?? ''),
            ];
        }

        $missing = array_keys(array_filter($items, fn ($i) => empty($i['poster'])));
        if (!empty($missing)) {
            foreach ((new MirainimeScraperService())->fetchPosters($missing) as $slug => $poster) {
                $items[$slug]['poster'] = $poster;
            }
        }

        return array_values($items);
    }

    public function selectScheduleDay(string $day): void
    {
        $this->selectedScheduleDay = $day;
    }

    /**
     * Beranda hanya tampil saat tidak ada pencarian/filter dan di halaman 1.
     */
    public function getShowHomeProperty(): bool
    {
        return blank($this->search) && blank($this->genre) && blank($this->status) && $this->pageNumber === 1;
    }

    public function loadAnimes(): void
    {
        $scraper = new MirainimeScraperService();
        if (filled($this->search)) {
            $cacheKey = 'v1_search_' . md5($this->search);
            $this->animes = $this->rememberCache($cacheKey, 3600, function () use ($scraper) {
                return $scraper->searchAnime($this->search);
            });
        } elseif (filled($this->genre)) {
            $cacheKey = "v1_genre_{$this->genre}_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 43200, function () use ($scraper) {
                return $scraper->scrapeAnimeByGenre($this->genre, $this->pageNumber);
            });
        } else {
            $cacheKey = "v1_list_{$this->status}_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 43200, function () use ($scraper) {
                return $scraper->scrapeAnimeList($this->pageNumber, $this->status);
            });
        }
    }

    public function updatedSearch(): void
    {
        $this->pageNumber = 1;
        $this->genre = '';
        $this->status = '';
        $this->loadAnimes();
    }

    public function updatedGenre(): void
    {
        $this->pageNumber = 1;
        $this->search = '';
        $this->status = '';
        $this->loadAnimes();
    }

    public function updatedStatus(): void
    {
        $this->pageNumber = 1;
        $this->search = '';
        $this->genre = '';
        $this->loadAnimes();
    }

    public function nextPage(): void
    {
        $this->pageNumber++;
        $this->loadAnimes();
    }

    public function previousPage(): void
    {
        if ($this->pageNumber > 1) {
            $this->pageNumber--;
            $this->loadAnimes();
        }
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->genre = '';
        $this->status = '';
        $this->pageNumber = 1;
        $this->loadAnimes();
    }
}
