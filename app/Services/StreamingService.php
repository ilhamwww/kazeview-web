<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\ScraperSetting;
use GuzzleHttp\Client;

class StreamingService
{
    /**
     * Ambil data beranda dengan cache.
     */
    public static function getHomepageData(): array
    {
        // Provider utama: Chitogenx (DutaMovie21).
        $chito = StreamSource::home();

        if (!empty($chito['heroes']) || !empty($chito['sections'])) {
            return $chito;
        }

        return ['heroes' => [], 'sections' => []];
    }

    /**
     * Pencarian katalog.
     */
    public static function search(string $query, int $page = 1, int $limit = 8): array
    {
        $chito = StreamSource::search($query, $page, max($limit, 20));

        if (!empty($chito['data'])) {
            return $chito['data'];
        }

        return [];
    }

    /**
     * Daftar film dengan paginasi.
     */
    public static function getMovies(int $page = 1, int $limit = 36, string $sort = 'createdAt'): array
    {
        $chito = StreamSource::listing('movie', $page, $limit);

        if (!empty($chito['data'])) {
            return $chito;
        }

        return ['data' => [], 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'totalPages' => 0]];
    }

    /**
     * Film trending.
     */
    public static function getTrendingMovies(int $limit = 5, string $period = '7d'): array
    {
        $chito = StreamSource::trending('movie', $limit);

        if (!empty($chito)) {
            return $chito;
        }

        return [];
    }

    /**
     * Daftar serial TV dengan paginasi.
     */
    public static function getSeries(int $page = 1, int $limit = 36, string $sort = 'createdAt'): array
    {
        $chito = StreamSource::listing('series', $page, $limit);

        if (!empty($chito['data'])) {
            return $chito;
        }

        return ['data' => [], 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'totalPages' => 0]];
    }


    /**
     * Daftar drama Korea.
     */
    public static function getKoreanDrama(int $page = 1, int $limit = 36, string $sort = 'latest'): array
    {
        $chito = ChitogenxService::koreanDrama($page, $limit);

        if (!empty($chito['data'])) {
            return $chito;
        }

        $fetchLimit = ($page === 1 && $limit < 36) ? 36 : $limit;
        return ['data' => [], 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'totalPages' => 0]];
    }

    /**
     * Serial TV trending.
     */
    public static function getTrendingSeries(int $limit = 5, string $period = '7d'): array
    {
        $chito = StreamSource::trending('series', $limit);

        if (!empty($chito)) {
            return $chito;
        }

        return [];
    }

    /**
     * Daftar koleksi.
     */
    public static function getCollections(): array
    {
        $chito = ChitogenxService::collections();

        if (!empty($chito)) {
            return $chito;
        }

        return [];
    }


    /**
     * Detail koleksi berdasarkan slug.
     */
    public static function getCollectionDetail(string $slug): ?array
    {
        $chito = ChitogenxService::collectionDetail($slug);

        if ($chito) {
            return $chito;
        }

        return null;
    }





    /**
     * Map individual media item from API format to a consistent internal format.
     */
    private static function mapMediaItem(array $item): ?array
    {
        // Items in sections may wrap contents in a nested "content" property.
        $content = $item['content'] ?? $item;

        $id = $content['id'] ?? $item['id'] ?? null;
        if (!$id) {
            return null;
        }

        $title = $content['title'] ?? $content['name'] ?? $item['title'] ?? 'No Title';
        $contentType = $item['contentType'] ?? $content['contentType'] ?? 'movie';

        // Build TMDB image URLs if paths are set
        $posterPath = $content['posterPath'] ?? null;
        $backdropPath = $content['backdropPath'] ?? null;
        
        $thumbnail = $posterPath ? 'https://image.tmdb.org/t/p/w500' . $posterPath : 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=600&q=80';
        $banner = $backdropPath ? 'https://image.tmdb.org/t/p/original' . $backdropPath : ($posterPath ? 'https://image.tmdb.org/t/p/original' . $posterPath : 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1600&q=80');

        $rating = $content['voteAverage'] ?? '0.0';
        if (is_numeric($rating)) {
            $rating = number_format((float)$rating, 1);
        }

        $year = '';
        if (!empty($content['releaseDate'])) {
            $year = substr($content['releaseDate'], 0, 4);
        } elseif (!empty($content['firstAirDate'])) {
            $year = substr($content['firstAirDate'], 0, 4);
        }

        // Genres mapping
        $genres = [];
        if (!empty($content['genres']) && is_array($content['genres'])) {
            foreach ($content['genres'] as $genre) {
                if (isset($genre['name'])) {
                    $genres[] = $genre['name'];
                }
            }
        }
        $genreStr = implode(', ', $genres);

        return [
            'id' => $id,
            'title' => $title,
            'type' => $contentType,
            'quality' => $content['quality'] ?? 'WEB-DL',
            'year' => $year,
            'rating' => $rating,
            'comments' => $content['commentCount'] ?? 0,
            'duration' => isset($content['runtime']) ? $content['runtime'] . 'm' : '',
            'genre' => $genreStr,
            'thumbnail' => $thumbnail,
            'banner' => $banner,
            // Logo judul transparan dari TMDB (dipakai hero slider sebagai pengganti teks)
            'logo' => !empty($content['logoPath'] ?? $item['logoPath'] ?? null) ? 'https://image.tmdb.org/t/p/w500' . ($content['logoPath'] ?? $item['logoPath']) : null,
            'description' => $content['overview'] ?? '',
            'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', // Default demo url
            'slug' => $content['slug'] ?? '',
        ];
    }

    /**
     * Detail serial.
     */
    public static function getSeriesDetail(string $slug)
    {
        if (str_starts_with($slug, 'mb-')) {
            $mbDetail = NartoMovieBoxService::getSeriesDetail($slug);
            if ($mbDetail) {
                return $mbDetail;
            }
            // Seamless Failover: jika MovieBox series gagal / 404, coba cari padanannya di Rebahin
            try {
                $cleanTitle = str_replace('-', ' ', preg_replace('/^mb-/', '', $slug));
                $rbMatch = RebahinService::getIndonesiaCatalog('series', 1, $cleanTitle);
                if (!empty($rbMatch['data'][0]['raw_slug'])) {
                    $rbDetail = RebahinService::getSeriesDetail('rb-' . $rbMatch['data'][0]['raw_slug']);
                    if ($rbDetail) {
                        $rbDetail['id'] = $slug;
                        $rbDetail['slug'] = $slug;
                        return $rbDetail;
                    }
                }
            } catch (\Throwable $e) {}
            return null;
        }

        if (str_starts_with($slug, 'rb-')) {
            return RebahinService::getSeriesDetail($slug);
        }

        // Provider utama: Chitogenx.
        $chito = StreamSource::detail($slug);

        if ($chito && ($chito['type'] ?? '') === 'series') {
            return $chito;
        }

        return null;
    }

    /**
     * Detail musim serial.
     */
    public static function getSeriesSeasonDetail(string $slug, int $seasonNumber)
    {
        if (str_starts_with($slug, 'mb-')) {
            $s = NartoMovieBoxService::getSeriesDetail($slug);
            return [
                'season' => $s['defaultSeason'] ?? ['seasonNumber' => $seasonNumber, 'episodes' => []]
            ];
        }

        if (str_starts_with($slug, 'rb-')) {
            $s = RebahinService::getSeriesDetail($slug);
            return [
                'season' => $s['defaultSeason'] ?? ['seasonNumber' => $seasonNumber, 'episodes' => []]
            ];
        }

        if (!str_starts_with($slug, 'mb-')) {
            $chitoSeason = StreamSource::season($slug, $seasonNumber);

            if ($chitoSeason && !empty($chitoSeason['season']['episodes'])) {
                return $chitoSeason;
            }
        }

        return null;
    }

    /**
     * Check if a stream URL contains an expiration timestamp that has already passed or is close to expiring.
     */
    public static function isStreamExpired(?array $stream): bool
    {
        if (!$stream || empty($stream['video_url'])) {
            return true;
        }

        $videoUrl = $stream['video_url'];
        if (preg_match('/\.(\d{10})-/', $videoUrl, $matches)) {
            $exp = (int) $matches[1];
            // If less than 5 minutes remaining or expired, treat as expired
            if (($exp - time()) < 300) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get non-blocking valid stream from cache if available and not expired.
     */
    public static function getValidCachedEpisodeStream(string $episodeId): ?array
    {
        // Chitogenx: URL bertanda tangan, selalu ambil baru.
        if (str_starts_with($episodeId, ChitogenxService::ID_PREFIX . ':')) {
            return null;
        }

        $cached = Cache::get('episode_stream_' . $episodeId);
        if ($cached && !self::isStreamExpired($cached)) {
            return $cached;
        }
        return null;
    }

    /**
     * Fetch Stream and Subtitles for a specific Episode ID
     */
    public static function getEpisodeStream(string $episodeId, bool $force = false)
    {
        // Indocast Filmbox: fb:<detailPath>:<season>:<episode>
        if (str_starts_with($episodeId, 'fb:')) {
            $parts = explode(':', $episodeId);
            $path = $parts[1] ?? '';
            $season = isset($parts[2]) ? (int) $parts[2] : null;
            $episode = isset($parts[3]) ? (int) $parts[3] : null;

            if ($path !== '') {
                $play = StreamSource::play($path, $season, $episode);

                if ($play) {
                    return $play;
                }
            }
        }

        if (str_starts_with($episodeId, 'mb-')) {
            if (preg_match('/^mb-(.*?)-ep-(\d+)$/', $episodeId, $m)) {
                return NartoMovieBoxService::resolveEpisodeStream('mb-' . $m[1], (int)$m[2]);
            }
        }

        if (str_starts_with($episodeId, 'rb-')) {
            return RebahinService::resolveStream($episodeId);
        }

        // Provider utama: Chitogenx. URL bertanda tangan -> tanpa cache.
        $chitoEpisode = ChitogenxService::parseEpisodeId($episodeId);

        if ($chitoEpisode) {
            $play = StreamSource::play(
                $chitoEpisode['slug'],
                $chitoEpisode['season'],
                $chitoEpisode['episode']
            );

            return $play ?: null;
        }

        return null;
    }



    /**
     * Detail film.
     */
    public static function getMovieDetail(string $slug)
    {
        if (str_starts_with($slug, 'mb-')) {
            $mbDetail = NartoMovieBoxService::getMovieDetail($slug);
            if ($mbDetail) {
                return $mbDetail;
            }
            // Seamless Failover: jika MovieBox gagal / 404, coba cari padanannya di Rebahin
            try {
                $cleanTitle = str_replace('-', ' ', preg_replace('/^mb-/', '', $slug));
                $rbMatch = RebahinService::getIndonesiaCatalog('movie', 1, $cleanTitle);
                if (!empty($rbMatch['data'][0]['raw_slug'])) {
                    $rbDetail = RebahinService::getMovieDetail('rb-' . $rbMatch['data'][0]['raw_slug']);
                    if ($rbDetail) {
                        $rbDetail['id'] = $slug;
                        $rbDetail['slug'] = $slug;
                        return $rbDetail;
                    }
                }
            } catch (\Throwable $e) {}
            return null;
        }

        if (str_starts_with($slug, 'rb-')) {
            return RebahinService::getMovieDetail($slug);
        }

        if (str_starts_with($slug, 'ix-')) {
            return IndoxxiTechService::getMovieDetail($slug);
        }

        // Provider utama: Chitogenx.
        $chito = StreamSource::detail($slug);

        if ($chito && ($chito['type'] ?? '') === 'movie') {
            return $chito;
        }

        return null;
    }


    /**
     * Get non-blocking valid stream from cache if available and not expired.
     */
    public static function getValidCachedMovieStream(string $movieId): ?array
    {
        // Chitogenx: URL bertanda tangan, selalu ambil baru.
        if (str_starts_with($movieId, ChitogenxService::ID_PREFIX . ':')) {
            return null;
        }

        $cached = Cache::get('movie_stream_' . $movieId);
        if ($cached && !self::isStreamExpired($cached)) {
            return $cached;
        }
        return null;
    }



    /**
     * Fetch Stream and Subtitles for a specific Movie ID
     */
    public static function getMovieStream(string $movieId, bool $force = false)
    {
        // Indocast Filmbox: fb:<detailPath>
        if (str_starts_with($movieId, 'fb:')) {
            $path = substr($movieId, 3);
            $play = StreamSource::play($path);

            if ($play) {
                return $play;
            }
        }

        // Provider utama: Chitogenx. URL bertanda tangan -> tanpa cache.
        $chitoMovie = ChitogenxService::parseEpisodeId($movieId);

        if ($chitoMovie && $chitoMovie['season'] === null) {
            $play = StreamSource::play($chitoMovie['slug']);

            return $play ?: null;
        }

        if (str_starts_with($movieId, 'mb-')) {
            $detail = NartoMovieBoxService::getMovieDetail($movieId);
            if ($detail && !empty($detail['video_url'])) {
                return [
                    'video_url' => $detail['video_url'],
                    'subtitles' => $detail['subtitles'] ?? [],
                ];
            }
        }

        if (str_starts_with($movieId, 'rb-')) {
            $rbStream = RebahinService::resolveStream($movieId);
            if ($rbStream && !empty($rbStream['video_url'])) {
                return $rbStream;
            }


            return null;
        }

        return null;
    }

    /**
     * Legacy helper to retrieve dummy data for WatchMovie fallback.
     */
    public static function getDummyData(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Carolina Caroline',
                'slug' => 'carolina-caroline',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '6.7',
                'comments' => 0,
                'duration' => '2h 10m',
                'genre' => 'Drama, Romance',
                'thumbnail' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1600&q=80',
                'description' => 'A beautifully shot romantic drama detailing the life of Carolina and her journey through modern love, heartbreak, and self-discovery.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            ],
            [
                'id' => 2,
                'title' => 'Tuner',
                'slug' => 'tuner',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '7.0',
                'comments' => 0,
                'duration' => '1h 45m',
                'genre' => 'Action, Thriller',
                'thumbnail' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?auto=format&fit=crop&w=1600&q=80',
                'description' => 'A high-octane thriller about an expert mechanic drawn into the underground crime syndicate as a getaway driver.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            ],
            [
                'id' => 3,
                'title' => 'Blue Heron',
                'slug' => 'blue-heron',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '7.5',
                'comments' => 0,
                'duration' => '1h 55m',
                'genre' => 'Drama, Mystery',
                'thumbnail' => 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=1600&q=80',
                'description' => 'An investigative drama set in the calm waters of a lakeside town, where a local legend hides a darker secret.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            ],
            [
                'id' => 4,
                'title' => 'Chapter 51',
                'slug' => 'chapter-51',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '8.1',
                'comments' => 0,
                'duration' => '2h 15m',
                'genre' => 'Sci-Fi, Adventure',
                'thumbnail' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1600&q=80',
                'description' => 'The ultimate chapter of a legendary saga. Earth discovers a wormhole in Sector 51, leading to an unknown civilization.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
            ],
            [
                'id' => 5,
                'title' => 'ChaO',
                'slug' => 'chao',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2025',
                'rating' => '8.2',
                'comments' => 0,
                'duration' => '1h 50m',
                'genre' => 'Animation, Fantasy',
                'thumbnail' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&w=1600&q=80',
                'description' => 'An artistic anime movie following the surreal adventures of a girl trapped in a kaleidoscope world of colors and sounds.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
            ],
            [
                'id' => 6,
                'title' => 'Billie Eilish - Hit Me Hard and Soft: The Tour Live in 3D',
                'slug' => 'billie-eilish-hit-me-hard',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2024',
                'rating' => '7.5',
                'comments' => 0,
                'duration' => '1h 30m',
                'genre' => 'Documentary, Music',
                'thumbnail' => 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=1600&q=80',
                'description' => 'Experience the immersive concert experience of Billie Eilish\'s worldwide tour, filmed live in stereoscopic 3D.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
            ],
            [
                'id' => 7,
                'title' => 'Pegasus 2',
                'slug' => 'pegasus-2',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2024',
                'rating' => '7.2',
                'comments' => 0,
                'duration' => '2h 00m',
                'genre' => 'Action, Comedy',
                'thumbnail' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=1600&q=80',
                'description' => 'The return of the legendary rally driver as he mentors a new generation of racers in the ultimate desert speed duel.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            ],
            [
                'id' => 8,
                'title' => 'Palm Trees in the Snow',
                'slug' => 'palm-trees-in-the-snow',
                'type' => 'Movie',
                'quality' => 'BLU-RAY',
                'year' => '2015',
                'rating' => '7.4',
                'comments' => 0,
                'duration' => '2h 43m',
                'genre' => 'Drama, History',
                'thumbnail' => 'https://images.unsplash.com/photo-1482862549707-f63cb32c5fd9?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1482862549707-f63cb32c5fd9?auto=format&fit=crop&w=1600&q=80',
                'description' => 'A discovery of a long-lost letter prompts a journey back to the equatorial colonies, revealing an epic story of forbidden love.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            ],
            [
                'id' => 9,
                'title' => 'Goal III: Taking on the World',
                'slug' => 'goal-iii',
                'type' => 'Movie',
                'quality' => 'BLU-RAY',
                'year' => '2009',
                'rating' => '4.0',
                'comments' => 0,
                'duration' => '1h 36m',
                'genre' => 'Drama, Sports',
                'thumbnail' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=1600&q=80',
                'description' => 'Two young football players get selected to play for their respective national teams at the biggest sporting event on Earth.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            ],
            [
                'id' => 10,
                'title' => 'Goal II: Living the Dream',
                'slug' => 'goal-ii',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2007',
                'rating' => '6.0',
                'comments' => 0,
                'duration' => '1h 55m',
                'genre' => 'Drama, Sports',
                'thumbnail' => 'https://images.unsplash.com/photo-1518063319789-7217e6706b04?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1518063319789-7217e6706b04?auto=format&fit=crop&w=1600&q=80',
                'description' => 'Santiago Munez secures a high-profile transfer to Real Madrid, but fame and success begin to impact his personal life.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
            ],
            [
                'id' => 11,
                'title' => 'The Iron Giant',
                'slug' => 'the-iron-giant',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '1999',
                'rating' => '8.0',
                'comments' => 2,
                'duration' => '1h 26m',
                'genre' => 'Animation, Sci-Fi',
                'thumbnail' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=1600&q=80',
                'description' => 'A young boy befriends a giant alien robot that a paranoid government agent wants to destroy.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
            ],
            [
                'id' => 12,
                'title' => 'Husbands in Action',
                'slug' => 'husbands-in-action',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '7.8',
                'comments' => 15,
                'duration' => '1h 48m',
                'genre' => 'Comedy',
                'thumbnail' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1600&q=80',
                'description' => 'Three middle-aged husbands hatch a hilarious plan to reclaim their youth, leading to comedic errors and chaotic adventures.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
            ],
            [
                'id' => 13,
                'title' => 'Voicemails for Isabelle',
                'slug' => 'voicemails-for-isabelle',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '6.6',
                'comments' => 30,
                'duration' => '1h 52m',
                'genre' => 'Drama, Romance',
                'thumbnail' => 'https://images.unsplash.com/photo-1514565131-fce0801e5785?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1514565131-fce0801e5785?auto=format&fit=crop&w=1600&q=80',
                'description' => 'A romantic story told through a series of voice messages left on an answering machine over the course of a year.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            ],
        ];
    }
}