<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

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

    public function mount($slug)
    {
        $this->movieSlug = $slug;

        // Auto-redirect Rebahin slug to canonical IDLIX movie if available
        if (str_starts_with($slug, 'rb-')) {
            try {
                $rbDetail = StreamingService::getMovieDetail($slug);
                if ($rbDetail) {
                    $idlixMovie = StreamingService::findIdlixMovieByTitle($rbDetail['title'] ?? '', $rbDetail['year'] ?? null);
                    if ($idlixMovie && !empty($idlixMovie['slug'])) {
                        return redirect()->to(self::getUrl(['slug' => $idlixMovie['slug']], panel: 'streaming'));
                    }
                }
            } catch (\Throwable $e) {
                // Ignore and proceed
            }
        }

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

        // Setup servers (HLS vs Embed)
        $servers = [];
        $isEmbedMovie = !empty($this->media['isEmbed']);

        if ($isEmbedMovie) {
            // Server 1 (HD) uses native Artplayer via Stream Relay
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
            $servers[] = [
                'id' => 'server1',
                'name' => 'Server 1 (HD)',
                'type' => 'hls',
                'embed_url' => '',
            ];

            if (!empty($this->media['mirror_servers'])) {
                foreach ($this->media['mirror_servers'] as $idx => $s) {
                    $servers[] = [
                        'id' => 'server_mirror_' . ($idx + 1),
                        'name' => 'Server ' . ($idx + 2) . ' (Mirror)',
                        'type' => 'embed',
                        'embed_url' => $s['embed_url'] ?? '',
                    ];
                }
            }
        }
        $this->servers = $servers;

        $stream = !empty($this->media['id']) ? StreamingService::getValidCachedMovieStream($this->media['id']) : null;
        if (!$stream && !empty($this->media['id']) && str_starts_with($this->media['id'], 'rb-')) {
            $stream = StreamingService::getMovieStream($this->media['id']);
        }
        if ($stream) {
            $this->media['video_url'] = $stream['video_url'] ?? '';
            $this->media['subtitles'] = $stream['subtitles'] ?? [];
        } else {
            $this->media['video_url'] = '';
            $this->media['subtitles'] = [];
        }

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
            $thumbnail = $this->media['poster'] ?? $this->media['thumbnail'] ?? null;

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
