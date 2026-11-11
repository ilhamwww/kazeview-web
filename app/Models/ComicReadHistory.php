<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComicReadHistory extends Model
{
    protected $fillable = [
        'user_id', 'comic_id', 'title', 'cover', 'format', 'provider',
        'last_chapter_id', 'last_chapter_label', 'read_chapters',
        'last_progress', 'last_panel_index', 'last_scroll_y', 'last_chapter_number',
    ];

    protected $casts = [
        'read_chapters' => 'array',
        'last_progress' => 'integer',
        'last_panel_index' => 'integer',
        'last_scroll_y' => 'integer',
        'last_chapter_number' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Catat chapter yang dibuka. Dipanggil dari KomikReader::mount(). */
    public static function record(int $userId, string $comicId, array $comic, array $chapter): static
    {
        $provider = match (true) {
            str_starts_with($comicId, 'native__') => 'native',
            str_starts_with($comicId, 'wurmz__') => 'wurmz',
            str_starts_with($comicId, 'izanami__') => 'izanami',
            str_starts_with($comicId, 'kiryuu__') => 'kiryuu',
            default => 'shinigami',
        };

        $row = static::firstOrNew(['user_id' => $userId, 'comic_id' => mb_substr($comicId, 0, 255)]);
        $read = is_array($row->read_chapters) ? $row->read_chapters : [];
        $chId = mb_substr((string) ($chapter['id'] ?? ''), 0, 100);
        if ($chId !== '' && !in_array($chId, $read, true)) {
            $read[] = $chId;
        }

        $row->fill([
            'title' => mb_substr((string) ($comic['title'] ?? ''), 0, 500) ?: null,
            'cover' => mb_substr((string) ($comic['cover'] ?? ''), 0, 1000) ?: null,
            'format' => mb_substr((string) ($comic['format'] ?? $comic['type'] ?? ''), 0, 30) ?: null,
            'provider' => $provider,
            'last_chapter_id' => $chId ?: null,
            'last_chapter_label' => mb_substr((string) ($chapter['label'] ?? ''), 0, 100) ?: null,
            'last_chapter_number' => isset($chapter['number']) ? (float)$chapter['number'] : null,
            'read_chapters' => array_values(array_slice($read, -5000)),
        ]);
        $row->updated_at = now(); // selalu naik walau chapter sama dibuka ulang
        $row->save();

        return $row;
    }

    /** Catat progress membaca realtime (progress %, panel index, scroll Y). */
    public static function recordProgress(int $userId, string $comicId, array $data): static
    {
        $provider = match (true) {
            str_starts_with($comicId, 'native__') => 'native',
            str_starts_with($comicId, 'wurmz__') => 'wurmz',
            str_starts_with($comicId, 'izanami__') => 'izanami',
            str_starts_with($comicId, 'kiryuu__') => 'kiryuu',
            default => 'shinigami',
        };

        $row = static::firstOrNew(['user_id' => $userId, 'comic_id' => mb_substr($comicId, 0, 255)]);
        $read = is_array($row->read_chapters) ? $row->read_chapters : [];
        $chId = mb_substr((string) ($data['chapter_id'] ?? ''), 0, 100);
        if ($chId !== '' && !in_array($chId, $read, true)) {
            $read[] = $chId;
        }

        $fill = [
            'provider' => $provider,
            'last_chapter_id' => $chId ?: ($row->last_chapter_id ?? null),
            'last_chapter_label' => mb_substr((string) ($data['chapter_label'] ?? ''), 0, 100) ?: ($row->last_chapter_label ?? null),
            'last_chapter_number' => isset($data['chapter_number']) ? (float)$data['chapter_number'] : ($row->last_chapter_number ?? null),
            'last_progress' => min(100, max(0, (int) ($data['progress'] ?? 0))),
            'last_panel_index' => max(1, (int) ($data['panel_index'] ?? 1)),
            'last_scroll_y' => max(0, (int) ($data['scroll_y'] ?? 0)),
            'read_chapters' => array_values(array_slice($read, -5000)),
        ];

        if (!empty($data['comic_title'])) {
            $fill['title'] = mb_substr((string)$data['comic_title'], 0, 500);
        }
        if (!empty($data['comic_cover'])) {
            $fill['cover'] = mb_substr((string)$data['comic_cover'], 0, 1000);
        }
        if (!empty($data['comic_format'])) {
            $fill['format'] = mb_substr((string)$data['comic_format'], 0, 30);
        }

        $row->fill($fill);
        $row->updated_at = now();
        $row->save();

        return $row;
    }
}
