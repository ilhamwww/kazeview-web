<?php

namespace App\Filament\Pages;

use App\Jobs\RatePhotoQuality;
use App\Models\Content;
use App\Models\DownloadData;
use App\Models\ListDownloaded;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DownloadGalleryPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.download-gallery';

    protected static ?string $title = 'Galeri & Sortir Foto';

    public $id = null;

    public $downloadId = null;

    public $contentId = null;

    public ?DownloadData $download = null;

    public ?Content $content = null;

    public static function getRouteName(?string $panel = null): string
    {
        return 'filament.admin.pages.download-gallery';
    }

    public static function getRoutePath(?string $panel = null): string
    {
        return '/download-gallery/{id}';
    }

    public function mount($id): void
    {
        $this->id = $id;

        // Mendukung ID DownloadData maupun ID Content agar fleksibel
        $this->download = DownloadData::with(['content', 'folders'])->find($id)
            ?? DownloadData::with(['content', 'folders'])->where('id_content', $id)->first();

        abort_unless($this->download, 404, 'Data download tidak ditemukan.');

        $this->downloadId = $this->download->id;
        $this->content = $this->download->content;
        $this->contentId = $this->content?->id;
    }

    public function getTitle(): string
    {
        $contentTitle = $this->content?->title ?? 'Event';
        $folderName = $this->download?->folder_name ?? 'Folder';

        return "Galeri Sortir: {$contentTitle} ({$folderName})";
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/contents') => 'Contents',
            url('/admin/content-download-page/' . ($this->contentId ?? '')) => $this->content?->title ?? 'Download Data',
            'Galeri Sortir Foto',
        ];
    }

    public function getPhotosData(): array
    {
        if (! $this->downloadId) {
            return [];
        }

        $query = ListDownloaded::query()
            ->with(['folder', 'motorSearchIndex'])
            ->where('id_download', $this->downloadId);

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $query->orderByRaw("
                COALESCE(
                    CAST(substring(split_part(file_name, '.', 1) FROM '[0-9]+$') AS INT),
                    0
                ) ASC,
                file_name ASC
            ");
        } elseif ($driver === 'mysql') {
            $query->orderByRaw("
                CAST(
                    IFNULL(REGEXP_SUBSTR(SUBSTRING_INDEX(file_name, '.', 1), '[0-9]+$'), '0')
                    AS UNSIGNED
                ) ASC,
                file_name ASC
            ");
        } else {
            $query->orderBy('file_name', 'asc');
        }

        $photos = $query->get();
        $disk = Storage::disk('public');

        return $photos->map(function (ListDownloaded $photo) use ($disk): array {
            $path = $photo->storagePath();
            $exists = $disk->exists($path);
            $index = $photo->motorSearchIndex;
            $d = $index?->descriptor ?? [];

            $aiSummary = null;
            if ($index && $index->status === 'indexed') {
                $brand = ($d['brand_guess'] ?? 'unknown') !== 'unknown' ? ucfirst($d['brand_guess']) : '';
                $model = ($d['model_guess'] ?? 'unknown') !== 'unknown' ? strtoupper($d['model_guess']) : '';
                $color = ($d['primary_color'] ?? 'unknown') !== 'unknown' ? ucfirst($d['primary_color']) : '';
                $plate = $d['license_plate'] ?? null;

                $parts = array_filter([$brand, $model, $color ? "({$color})" : '', $plate && $plate !== 'unknown' ? "Plat: " . strtoupper($plate) : '']);
                $aiSummary = ! empty($parts) ? implode(' ', $parts) : 'Terdeteksi Motor';
            } elseif ($index && $index->status === 'failed') {
                $aiSummary = 'Gagal Index AI';
            }

            return [
                'id' => $photo->id,
                'file_name' => $photo->file_name,
                'display_name' => pathinfo($photo->file_name, PATHINFO_FILENAME),
                'folder' => $photo->folder?->relative_path ?? 'Root',
                'file_size' => $photo->file_size ? number_format($photo->file_size / 1048576, 1) . ' MB' : null,
                'original_url' => $exists ? $disk->url($path) : null,
                'grid_thumb' => route('photos.thumbnail', ['photo' => $photo->id, 'mode' => 'grid']),
                'fit_thumb' => route('photos.thumbnail', ['photo' => $photo->id, 'mode' => 'fit']),
                'preview_url' => $exists ? route('admin.photos.preview', ['photo' => $photo->id]) : null,
                'has_original' => $exists,
                'ai_status' => $index?->status ?? 'none',
                'ai_summary' => $aiSummary,
                'rating' => $index?->quality_rating,
                'rating_focus' => $index?->quality_focus,
                'rating_framing' => $index?->quality_framing,
                'rating_cut' => $index?->quality_subject_cut,
                'rating_note' => $index?->quality_note,
            ];
        })->values()->all();
    }

    public function getFolderList(): array
    {
        if (! $this->download) {
            return [];
        }

        return $this->download->folders()
            ->orderBy('relative_path')
            ->pluck('relative_path', 'relative_path')
            ->all();
    }

    protected function getViewData(): array
    {
        return [
            'photosList' => $this->getPhotosData(),
            'folders' => $this->getFolderList(),
            'contentTitle' => $this->content?->title ?? 'Event',
            'folderName' => $this->download?->folder_name ?? 'Folder',
            'contentId' => (int) $this->contentId,
            'downloadId' => (int) $this->downloadId,
            'unratedCount' => $this->unratedPhotoCount(),
        ];
    }

    /** Jumlah foto di unduhan ini yang belum punya rating kualitas AI. */
    public function unratedPhotoCount(): int
    {
        if (! $this->downloadId) {
            return 0;
        }

        return ListDownloaded::query()
            ->where('id_download', $this->downloadId)
            ->whereDoesntHave(
                'motorSearchIndex',
                fn ($query) => $query->whereNotNull('quality_rating'),
            )
            ->count();
    }

    /**
     * Antrikan penilaian kualitas AI untuk foto yang belum punya rating.
     * Dipakai untuk foto lama yang sudah terindeks sebelum fitur rating ada.
     */
    public function rateMissingPhotos(): void
    {
        if (! $this->downloadId) {
            return;
        }

        $ids = ListDownloaded::query()
            ->where('id_download', $this->downloadId)
            ->whereDoesntHave(
                'motorSearchIndex',
                fn ($query) => $query->whereNotNull('quality_rating'),
            )
            ->orderBy('id')
            ->pluck('id');

        $total = 0;

        foreach ($ids as $id) {
            RatePhotoQuality::dispatch((int) $id)->afterCommit();
            $total++;
        }

        Notification::make()
            ->title(
                $total > 0
                    ? "{$total} foto masuk antrean rating"
                    : 'Semua foto sudah punya rating',
            )
            ->body(
                $total > 0
                    ? 'Rating dihitung di background oleh AI. Muat ulang halaman ini setelah selesai.'
                    : 'Tidak ada foto yang perlu dinilai.',
            )
            ->when(
                $total > 0,
                fn (Notification $notification) => $notification->success(),
                fn (Notification $notification) => $notification->warning(),
            )
            ->send();
    }

    /**
     * Detail index AI untuk satu foto, dipanggil dari lightbox lewat Livewire
     * supaya daftar descriptor tidak ikut membengkakkan payload galeri.
     *
     * @return array<string, mixed>|null
     */
    public function photoIndexDetail(int $photoId): ?array
    {
        $photo = ListDownloaded::query()
            ->with('motorSearchIndex')
            ->where('id_download', $this->downloadId)
            ->find($photoId);

        if (! $photo) {
            return null;
        }

        $index = $photo->motorSearchIndex;
        $descriptor = is_array($index?->descriptor) ? $index->descriptor : [];

        return [
            'id' => $photo->getKey(),
            'file_name' => $photo->file_name,
            'status' => $index?->status ?? 'none',
            'rating' => $index?->quality_rating,
            'focus' => $index?->quality_focus,
            'framing' => $index?->quality_framing,
            'subject_cut' => $index?->quality_subject_cut,
            'crop_severity' => $index?->quality_crop_severity,
            'quality_note' => $index?->quality_note,
            'quality_model' => $index?->quality_model,
            'quality_rated_at' => $index?->quality_rated_at
                ? $index->quality_rated_at->copy()->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
                : null,
            'vision_model' => $index?->vision_model,
            'embedding_model' => $index?->embedding_model,
            'prompt_version' => $index?->prompt_version,
            'dimensions' => $index?->embedding_dimensions,
            'indexed_at' => $index?->indexed_at
                ? $index->indexed_at->copy()->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
                : null,
            'error_message' => $index?->error_message,
            'brand' => $descriptor['brand_guess'] ?? null,
            'model' => $descriptor['model_guess'] ?? null,
            'category' => $descriptor['category'] ?? null,
            'primary_color' => $descriptor['primary_color'] ?? null,
            'secondary_colors' => array_values((array) ($descriptor['secondary_colors'] ?? [])),
            'license_plate' => $descriptor['license_plate'] ?? null,
            'decals' => array_values((array) ($descriptor['decals'] ?? [])),
            'accessories' => array_values((array) ($descriptor['accessories'] ?? [])),
            'visible_text' => array_values((array) ($descriptor['visible_text'] ?? [])),
            'helmets' => array_values((array) ($descriptor['helmets'] ?? [])),
            'rider_helmet' => $descriptor['rider_helmet'] ?? null,
            'distinctive_features' => array_values((array) ($descriptor['distinctive_features'] ?? [])),
            'descriptor' => $descriptor,
        ];
    }

    public function deletePhoto(int $photoId): bool
    {
        $photo = ListDownloaded::where('id_download', $this->downloadId)
            ->where('id', $photoId)
            ->first();

        if (! $photo) {
            Notification::make()
                ->title('Foto tidak ditemukan')
                ->danger()
                ->send();

            return false;
        }

        $fileName = $photo->file_name;
        $storagePath = $photo->storagePath();
        $disk = Storage::disk('public');

        // 1. Hapus file sumber asli jika ada
        if ($disk->exists($storagePath)) {
            $disk->delete($storagePath);
        }

        // 2. Bersihkan seluruh thumbnail cache foto ini
        foreach ($disk->files('thumbnails') as $cachedThumb) {
            if (str_starts_with(basename($cachedThumb), "{$photo->id}-")) {
                $disk->delete($cachedThumb);
            }
        }

        // 3. Hapus data record di DB (cascades ke motorSearchIndex)
        $photo->delete();

        // 4. Update count di download_data
        $remaining = ListDownloaded::where('id_download', $this->downloadId)->count();
        $this->download->update([
            'total_files' => $remaining,
        ]);

        Notification::make()
            ->title('Foto Terhapus')
            ->body("{$fileName} telah dihapus permanen.")
            ->success()
            ->send();

        return true;
    }

    public function bulkDeletePhotos(array $photoIds): int
    {
        if (empty($photoIds)) {
            return 0;
        }

        $photos = ListDownloaded::where('id_download', $this->downloadId)
            ->whereIn('id', $photoIds)
            ->get();

        $disk = Storage::disk('public');
        $deletedCount = 0;

        foreach ($photos as $photo) {
            $storagePath = $photo->storagePath();
            if ($disk->exists($storagePath)) {
                $disk->delete($storagePath);
            }

            foreach ($disk->files('thumbnails') as $cachedThumb) {
                if (str_starts_with(basename($cachedThumb), "{$photo->id}-")) {
                    $disk->delete($cachedThumb);
                }
            }

            $photo->delete();
            $deletedCount++;
        }

        $remaining = ListDownloaded::where('id_download', $this->downloadId)->count();
        $this->download->update([
            'total_files' => $remaining,
        ]);

        Notification::make()
            ->title('Hapus Masal Berhasil')
            ->body("{$deletedCount} foto berhasil dihapus permanen.")
            ->success()
            ->send();

        return $deletedCount;
    }
}
