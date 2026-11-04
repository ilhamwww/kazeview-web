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

        return $chapter && is_array($chapter->pages) ? $chapter->pages : [];
    }
}
