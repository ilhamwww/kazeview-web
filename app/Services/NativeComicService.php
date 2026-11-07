<?php

namespace App\Services;

use App\Models\NativeComic;
use App\Models\NativeComicChapter;
use Illuminate\Support\Facades\Cache;

class NativeComicService
{
    /**
     * Map model to UI shape
     */
    protected function mapItem(NativeComic $c): array
    {
        return [
            'id' => 'native__' . $c->slug,
            'slug' => $c->slug,
            'title' => $c->title,
            'cover' => $c->cover_path ?: $c->original_cover_url,
            'type' => $c->type,
            'format' => $c->format ?: $c->type,
            'status' => $c->status ?: 'Ongoing',
            'description' => $c->description ?: '',
            'author' => $c->author ?: '',
            'artist' => $c->artist ?: '',
            'latest_chapter' => $c->latest_chapter ?: '',
            'latest_chapter_time' => $c->latest_chapter_at ? $c->latest_chapter_at->diffForHumans() : '',
            'rating' => $c->rating,
        ];
    }

    public function getPopular(int $page = 1, int $limit = 48, ?string $format = null): array
    {
        $query = NativeComic::query();
        if ($format && in_array(strtolower($format), ['manhwa', 'manga', 'manhua'])) {
            $query->where('type', strtolower($format));
        }

        $paginator = $query->orderBy('rating', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'data' => array_map([$this, 'mapItem'], $paginator->items()),
            'total_page' => $paginator->lastPage(),
            'has_next' => $paginator->hasMorePages(),
        ];
    }

    public function getLatest(int $page = 1, int $limit = 48, ?string $format = null): array
    {
        $query = NativeComic::query();
        if ($format && in_array(strtolower($format), ['manhwa', 'manga', 'manhua'])) {
            $query->where('type', strtolower($format));
        }

        $paginator = $query->orderBy('updated_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'data' => array_map([$this, 'mapItem'], $paginator->items()),
            'total_page' => $paginator->lastPage(),
            'has_next' => $paginator->hasMorePages(),
        ];
    }

    public function search(string $query, int $page = 1, int $limit = 24): array
    {
        $q = trim($query);
        $paginator = NativeComic::query()
            ->where(function ($w) use ($q) {
                $w->where('title', 'ilike', "%{$q}%")
                  ->orWhere('alt_title', 'ilike', "%{$q}%");
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'data' => array_map([$this, 'mapItem'], $paginator->items()),
            'total_page' => $paginator->lastPage(),
            'has_next' => $paginator->hasMorePages(),
        ];
    }

    public function getDetail(string $idOrSlug): ?array
    {
        $slug = $idOrSlug;
        if (str_starts_with($idOrSlug, 'native__')) {
            $slug = substr($idOrSlug, strlen('native__'));
        }

        $comic = NativeComic::where('slug', $slug)->first();
        if (!$comic) {
            return null;
        }

        $chapters = $comic->chapters()->get()->map(function ($ch) {
            return [
                'id' => $ch->chapter_slug,
                'number' => (float) $ch->chapter_number,
                'label' => $ch->chapter_title ?: ('Chapter ' . $ch->chapter_number),
                'released_at' => $ch->released_at ? $ch->released_at->format('d M Y') : '',
            ];
        })->toArray();

        return [
            'id' => 'native__' . $comic->slug,
            'slug' => $comic->slug,
            'title' => $comic->title,
            'alt_title' => $comic->alt_title,
            'cover' => $comic->cover_path ?: $comic->original_cover_url,
            'banner' => $comic->cover_path ?: $comic->original_cover_url,
            'description' => $comic->description,
            'type' => $comic->type,
            'format' => $comic->type,
            'status' => $comic->status ?: 'Ongoing',
            'rating' => $comic->rating,
            'genres' => $comic->genres ?: [],
            'author' => $comic->author ?: '',
            'artist' => $comic->artist ?: '',
            'chapters' => $chapters,
        ];
    }

    public function getChapters(string $idOrSlug): array
    {
        $det = $this->getDetail($idOrSlug);
        return $det['chapters'] ?? [];
    }

    public function getChapterPages(string $comicId, string $chapterId): array
    {
        $slug = $comicId;
        if (str_starts_with($comicId, 'native__')) {
            $slug = substr($comicId, strlen('native__'));
        }

        $comic = NativeComic::where('slug', $slug)->first();
        if (!$comic) {
            return [];
        }

        $chapter = NativeComicChapter::where('comic_id', $comic->id)
            ->where(function ($w) use ($chapterId) {
                $w->where('chapter_slug', $chapterId)
                  ->orWhere('chapter_number', (float) $chapterId);
            })
            ->first();

        if (!$chapter) {
            return [];
        }

        // 1. Jika lembaran gambar sudah tersimpan di database lokal
        if (!empty($chapter->pages) && is_array($chapter->pages)) {
            return $chapter->pages;
        }

        // 2. Jika lembaran gambar belum terisi, fetch on-demand lewat ShinigamiService (via NUC relay) lalu simpan ke DB!
        if ($chapter->upstream_chapter_id) {
            try {
                $shinigami = app(ShinigamiService::class);
                $data = $shinigami->getChapterPages($chapter->upstream_chapter_id);
                if (!empty($data['pages']) && is_array($data['pages'])) {
                    // Update cache database agar request berikutnya instan!
                    $chapter->update(['pages' => $data['pages']]);
                    return $data['pages'];
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Native on-demand fetch pages error: " . $e->getMessage());
            }
        }

        // 3. ZERO-NUC FAILOVER: Jika NUC Relay sedang offline / gagal, cari otomatis dari Wurmz atau Kiryuu!
        $targetNum = (float) $chapter->chapter_number;
        $normTitle = $this->normalizeTitle($comic->title);
        $altTitles = array_filter(array_map([$this, 'normalizeTitle'], explode(',', (string) $comic->alt_title)));
        $validNorms = array_unique(array_merge([$normTitle], $altTitles));

        // 3a. Failover ke Wurmz Scans (Zero WAF di VPS)
        try {
            $wurmz = app(WurmzScraperService::class);
            $searchRes = $wurmz->search($comic->title);
            foreach ($searchRes['data'] ?? [] as $candidate) {
                $candNorm = $this->normalizeTitle($candidate['title'] ?? '');
                if (in_array($candNorm, $validNorms, true)) {
                    $wChs = $wurmz->getChapters($candidate['id']);
                    foreach ($wChs as $wc) {
                        if (abs(((float) ($wc['number'] ?? -1)) - $targetNum) < 0.001) {
                            $wPages = $wurmz->getChapterPages($candidate['id'], (string) $wc['id']);
                            if (!empty($wPages) && is_array($wPages)) {
                                $chapter->update(['pages' => $wPages]);
                                return $wPages;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Native Wurmz failover error: " . $e->getMessage());
        }

        // 3b. Failover ke Kiryuu ID (Direct VPS)
        try {
            $kiryuu = app(KiryuuScraperService::class);
            $kDetail = $kiryuu->getDetail('kiryuu__' . $comic->slug);
            $kComicId = null;

            if ($kDetail && in_array($this->normalizeTitle($kDetail['title'] ?? ''), $validNorms, true)) {
                $kComicId = $kDetail['id'];
            } else {
                $kSearch = $kiryuu->search($comic->title);
                foreach ($kSearch['data'] ?? [] as $cand) {
                    if (in_array($this->normalizeTitle($cand['title'] ?? ''), $validNorms, true)) {
                        $kComicId = $cand['id'];
                        break;
                    }
                }
            }

            if ($kComicId) {
                $kChs = $kiryuu->getChapters($kComicId);
                foreach ($kChs as $kc) {
                    if (abs(((float) ($kc['number'] ?? -1)) - $targetNum) < 0.001) {
                        $kData = $kiryuu->getChapterPages((string) $kc['id'], $kComicId);
                        if (!empty($kData['pages']) && is_array($kData['pages'])) {
                            $chapter->update(['pages' => $kData['pages']]);
                            return $kData['pages'];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Native Kiryuu failover error: " . $e->getMessage());
        }

        return [];
    }

    protected function normalizeTitle(string $title): string
    {
        $t = strtolower(trim($title));
        $t = preg_replace('/[^a-z0-9]+/', ' ', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }
}
