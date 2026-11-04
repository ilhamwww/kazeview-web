<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComicReadHistory extends Model
{
    protected $fillable = [
        'user_id', 'comic_id', 'title', 'cover', 'format', 'provider',
        'last_chapter_id', 'last_chapter_label', 'read_chapters',
    ];

    protected $casts = [
        'read_chapters' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Catat chapter yang dibuka. Dipanggil dari KomikReader::mount(). */
    public static function record(int $userId, string $comicId, array $comic, array $chapter): void
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
            'read_chapters' => array_values(array_slice($read, -5000)),
        ]);
        $row->updated_at = now(); // selalu naik walau chapter sama dibuka ulang
        $row->save();
    }
}
