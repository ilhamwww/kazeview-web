<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;
use App\Services\DirectStreamResolverService;

class WatchMovie extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch';
    protected static ?string $title = 'Watch Media';
    protected static ?string $slug = 'movie/{slug}';
    protected static string $view = 'filament.streaming.pages.watch-movie';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public $movieSlug;
    public ?array $media = null;
    public array $recommendations = [];
    public bool $isFavorited = false;
    public array $servers = [];
    public float $savedProgressSeconds = 0;

    public function mount($slug)
    {
        $this->movieSlug = $slug;

        $this->media = StreamingService::getMovieDetail($slug);

        if (!$this->media) {
            abort(404);
        }

        // Server-side subscription check
        if (auth()->check() && auth()->user()->isSubscriptionExpired()) {
            $this->media['video_url'] = '';
            $this->media['subtitles'] = [];
            $this->recommendations = [];
            $this->servers = [];
            return;
        }

        // Fetch direct stream first if available (IDLIX via NUC Relay / Rebahin / LK21)
        $stream = !empty($this->media['id']) ? StreamingService::getValidCachedMovieStream($this->media['id']) : null;
        if (!$stream && !empty($this->media['id'])) {
            $stream = StreamingService::getMovieStream($this->media['id']);
        }
        if (!$stream && !empty($this->media['title'])) {
            $stream = DirectStreamResolverService::resolveMovieStream($this->media['title'], $this->media['year'] ?? null);
        }
        if ($stream && !empty($stream['video_url'])) {
            $this->media['video_url'] = $stream['video_url'];
            $this->media['subtitles'] = $stream['subtitles'] ?? [];
        } else {
            $this->media['video_url'] = '';
            $this->media['subtitles'] = [];
        }

        // Setup servers (HLS vs Embed)
        $servers = [];
        $isEmbedMovie = !empty($this->media['isEmbed']);

        if ($isEmbedMovie) {
            // Server 1 (HD) uses native Artplayer via Stream Relay (Rebahin / Indonesia)
            $servers[] = [
                'id' => 'server1',
                'name' => 'Server 1 (HD)',
                'type' => 'hls',
                'embed_url' => '',
            ];

            $rbServers = $this->media['servers'] ?? [];
            foreach ($rbServers as $idx => $s) {
                $servers[] = [
                    'id' => 'server_mirror_' . ($idx + 1),
                    'name' => 'Server ' . ($idx + 2) . ' (Mirror)',
                    'type' => 'embed',
                    'embed_url' => $s['embed_url'] ?? '',
                ];
            }
        } else {
            // Jika ada Direct Stream HLS/Relay yang berhasil di-resolve, jadikan Server 1 Native Artplayer
            $hasDirectRelay = !empty($this->media['video_url']);
            if ($hasDirectRelay) {
                $servers[] = [
                    'id' => 'server_direct',
                    'name' => 'Server 1 (Native HD - Bebas Iklan)',
                    'type' => 'hls',
                    'embed_url' => '',
                ];
            }

            // Universal TMDB Embed Engine for Global Movies (Sebagai Server Cadangan)
            $tmdbId = $this->media['tmdb_id'] ?? ($this->media['raw_detail']['tmdbId'] ?? null);
            if (!$tmdbId && !empty($this->media['id']) && is_numeric($this->media['id'])) {
                $tmdbId = $this->media['id'];
            }

            $offset = $hasDirectRelay ? 1 : 0;

            if ($tmdbId) {
                $servers[] = [
                    'id' => 'server_vidlink',
                    'name' => 'Server ' . (1 + $offset) . ' (VidLink HD)',
                    'type' => 'embed',
                    'embed_url' => 'https://vidlink.pro/movie/' . $tmdbId . '?primaryColor=E50914&autoplay=false',
                ];
                $servers[] = [
                    'id' => 'server_2embed',
                    'name' => 'Server ' . (2 + $offset) . ' (2Embed)',
                    'type' => 'embed',
                    'embed_url' => 'https://www.2embed.cc/embed/' . $tmdbId,
                ];
                $servers[] = [
                    'id' => 'server_multiembed',
                    'name' => 'Server ' . (3 + $offset) . ' (MultiEmbed)',
                    'type' => 'embed',
                    'embed_url' => 'https://multiembed.mov/?video_id=' . $tmdbId . '&tmdb=1',
                ];
                $servers[] = [
                    'id' => 'server_vidsrc',
                    'name' => 'Server ' . (4 + $offset) . ' (VidSrc)',
                    'type' => 'embed',
                    'embed_url' => 'https://vidsrc.sh/embed/movie/' . $tmdbId,
                ];
            } elseif (!$hasDirectRelay) {
                $servers[] = [
                    'id' => 'server1',
                    'name' => 'Server 1 (HD)',
                    'type' => 'hls',
                    'embed_url' => '',
                ];
            }

            if (!empty($this->media['mirror_servers'])) {
                foreach ($this->media['mirror_servers'] as $idx => $s) {
                    $servers[] = [
                        'id' => 'server_mirror_' . ($idx + 1),
                        'name' => 'Server ' . ($idx + 5 + $offset) . ' (Mirror)',
                        'type' => 'embed',
                        'embed_url' => $s['embed_url'] ?? '',
                    ];
                }
            }
        }
        $this->servers = $servers;

        // Get recommendations
        $homeData = StreamingService::getHomepageData();
        $recommendations = [];
        foreach ($homeData['sections'] ?? [] as $section) {
            $recommendations = array_merge($recommendations, $section['items'] ?? []);
        }

        if (empty($recommendations)) {
            $recommendations = StreamingService::getDummyData();
        }

        $this->recommendations = array_values(array_filter($recommendations, fn($item) => ($item['slug'] ?? '') !== $slug));

        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', $this->movieSlug)
                ->where('item_type', 'movie')
                ->exists()
            : false;

        // Record Watch History
        if (auth()->check()) {
            $user = auth()->user();
            $movieTitle = $this->media['title'] ?? 'Movie';
            $thumbnail = $this->media['thumbnail'] ?? ($this->media['poster'] ?? ($this->media['posterPath'] ?? ($this->media['poster_path'] ?? ($this->media['banner'] ?? null))));

            $existing = $user->watchHistories()
                ->where('item_id', $this->movieSlug)
                ->where('item_type', 'movie')
                ->first();

            if ($existing && $existing->progress_seconds > 10 && $existing->progress_percent < 95) {
                $this->savedProgressSeconds = (float) $existing->progress_seconds;
            }

            $user->watchHistories()->updateOrCreate(
                [
                    'item_id' => $this->movieSlug,
                    'item_type' => 'movie',
                ],
                [
                    'title' => $movieTitle,
                    'thumbnail' => $thumbnail,
                    'last_episode' => '1',
                    'watched_episodes' => ['1'],
                    'url' => request()->url(),
                ]
            );
        }
    }

    public function toggleFavorite()
    {
        if (!auth()->check()) {
            return redirect()->to(route('filament.streaming.auth.login'));
        }

        $user = auth()->user();
        $favorite = $user->favorites()
            ->where('item_id', $this->movieSlug)
            ->where('item_type', 'movie')
            ->first();

        if ($favorite) {
            $favorite->delete();
            $this->isFavorited = false;
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Favorit')
                ->success()
                ->send();
        } else {
            $user->favorites()->create([
                'item_id' => $this->movieSlug,
                'item_type' => 'movie',
                'title' => $this->media['title'] ?? 'Movie Detail',
                'thumbnail' => $this->media['poster'] ?? $this->media['thumbnail'] ?? null,
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
