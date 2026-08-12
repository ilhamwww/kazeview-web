<?php

namespace App\Services;

use App\Models\DownloadData;
use App\Models\DownloadFolder;
use App\Models\ListDownloaded;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleDriveService
{
    private const FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';

    protected Google_Client $client;

    protected Google_Service_Drive $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(
            storage_path('app/credentials/credentials.json'),
        );
        $this->client->addScope(Google_Service_Drive::DRIVE);
        $this->client->setAccessType('offline');

        $this->service = new Google_Service_Drive($this->client);
    }

    public function download(Request $request): array
    {
        return $this->downloadFolder(
            (string) $request->input('folder_id'),
            (int) $request->input('id_content'),
        );
    }

    public function downloadFolder(string $folderId, int $contentId): array
    {
        try {
            if (! preg_match('/^[A-Za-z0-9_-]+$/', $folderId)) {
                throw new RuntimeException('Google Drive folder ID tidak valid.');
            }

            $scan = $this->scanFolderTree($folderId);

            if ($scan['total_images'] === 0) {
                return [
                    'status' => 'error',
                    'message' => "Folder {$scan['root_name']} tidak memiliki file gambar.",
                    'scan' => $scan,
                ];
            }

            $download = DownloadData::query()->firstOrCreate(
                [
                    'id_folder' => $folderId,
                    'id_content' => $contentId,
                ],
                [
                    'folder_name' => $scan['root_name'],
                    'total_files' => $scan['total_images'],
                ],
            );

            $download->update([
                'folder_name' => $scan['root_name'],
                'total_files' => $scan['total_images'],
            ]);

            $folderRecords = $this->persistFolders(
                $download,
                $scan['folders'],
            );

            $alreadyDownloaded = ListDownloaded::query()
                ->where('id_download', $download->getKey())
                ->pluck('id', 'id_file_in_gd');

            $downloadedCount = 0;
            $skippedCount = 0;

            foreach ($scan['images'] as $image) {
                $folderRecord = $image['folder_google_id'] === $folderId
                    ? null
                    : ($folderRecords[$image['folder_google_id']] ?? null);

                $relativePath = $image['relative_directory'] === ''
                    ? "{$image['id']}.jpg"
                    : "{$image['relative_directory']}/{$image['id']}.jpg";

                if ($alreadyDownloaded->has($image['id'])) {
                    $existing = ListDownloaded::query()->find(
                        $alreadyDownloaded[$image['id']],
                    );
                    $newStoragePath = "downloads/{$folderId}/{$relativePath}";
                    $oldStoragePath = $existing?->storagePath();

                    if (
                        $existing
                        && $oldStoragePath !== $newStoragePath
                        && Storage::disk('public')->exists($oldStoragePath)
                    ) {
                        Storage::disk('public')->move(
                            $oldStoragePath,
                            $newStoragePath,
                        );
                    }

                    if (
                        $existing
                        && Storage::disk('public')->exists($newStoragePath)
                    ) {
                        $existing->update([
                            'download_folder_id' => $folderRecord?->getKey(),
                            'relative_path' => $relativePath,
                            'mime_type' => $image['mime_type'],
                            'file_size' => $image['size'],
                            'drive_modified_at' => $image['modified_time'],
                            'file_name' => $image['name'],
                        ]);

                        $skippedCount++;

                        continue;
                    }
                }

                $response = $this->service->files->get(
                    $image['id'],
                    ['alt' => 'media'],
                );

                $storagePath = "downloads/{$folderId}/{$relativePath}";

                if (! Storage::disk('public')->put(
                    $storagePath,
                    $response->getBody()->getContents(),
                )) {
                    throw new RuntimeException(
                        "File {$image['name']} gagal disimpan.",
                    );
                }

                ListDownloaded::query()->updateOrCreate([
                    'id_download' => $download->getKey(),
                    'id_file_in_gd' => $image['id'],
                ], [
                    'download_folder_id' => $folderRecord?->getKey(),
                    'file_name' => $image['name'],
                    'id_folder' => $folderId,
                    'relative_path' => $relativePath,
                    'mime_type' => $image['mime_type'],
                    'file_size' => $image['size'],
                    'drive_modified_at' => $image['modified_time'],
                ]);

                $downloadedCount++;
            }

            $folderCount = count($scan['folders']);
            $ignoredCount = $scan['non_image_count'];

            return [
                'status' => $downloadedCount > 0 ? 'success' : 'info',
                'message' => "{$scan['root_name']}: {$folderCount} subfolder, "
                    . "{$scan['total_images']} gambar ditemukan. "
                    . "{$downloadedCount} baru, {$skippedCount} dilewati, "
                    . "{$ignoredCount} file non-gambar diabaikan.",
                'scan' => $scan,
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function scanFolderTree(string $rootFolderId): array
    {
        $root = $this->service->files->get(
            $rootFolderId,
            ['fields' => 'id,name,mimeType'],
        );

        if ($root->getMimeType() !== self::FOLDER_MIME_TYPE) {
            throw new RuntimeException('Google Drive ID bukan sebuah folder.');
        }

        $folders = [];
        $images = [];
        $nonImageCount = 0;
        $visited = [];

        $this->scanChildren(
            $rootFolderId,
            '',
            0,
            $visited,
            $folders,
            $images,
            $nonImageCount,
        );

        return [
            'root_id' => $rootFolderId,
            'root_name' => $root->getName(),
            'folders' => $folders,
            'images' => $images,
            'total_images' => count($images),
            'non_image_count' => $nonImageCount,
        ];
    }

    protected function scanChildren(
        string $folderId,
        string $relativeDirectory,
        int $depth,
        array &$visited,
        array &$folders,
        array &$images,
        int &$nonImageCount,
    ): int {
        if ($depth > 20) {
            throw new RuntimeException('Struktur folder melebihi 20 tingkat.');
        }

        if (isset($visited[$folderId])) {
            return 0;
        }

        $visited[$folderId] = true;
        $children = $this->listAllChildren($folderId);
        $directImages = 0;
        $totalImages = 0;
        $sortOrder = 0;
        $usedSegments = [];

        foreach ($children as $child) {
            if ($child->getMimeType() === self::FOLDER_MIME_TYPE) {
                $segment = $this->sanitizeFolderSegment(
                    $child->getName(),
                    $child->getId(),
                );
                $segmentKey = mb_strtolower($segment);

                if (isset($usedSegments[$segmentKey])) {
                    $segment .= '--' . Str::substr(
                        $child->getId(),
                        0,
                        8,
                    );
                }

                $usedSegments[mb_strtolower($segment)] = true;
                $childPath = $relativeDirectory === ''
                    ? $segment
                    : "{$relativeDirectory}/{$segment}";

                $folderIndex = count($folders);
                $folders[] = [
                    'google_folder_id' => $child->getId(),
                    'parent_google_folder_id' => $folderId,
                    'name' => $child->getName(),
                    'relative_path' => $childPath,
                    'depth' => $depth + 1,
                    'image_count' => 0,
                    'total_image_count' => 0,
                    'sort_order' => $sortOrder++,
                ];

                $childTotal = $this->scanChildren(
                    $child->getId(),
                    $childPath,
                    $depth + 1,
                    $visited,
                    $folders,
                    $images,
                    $nonImageCount,
                );

                $folders[$folderIndex]['image_count'] = collect($images)
                    ->where('folder_google_id', $child->getId())
                    ->count();
                $folders[$folderIndex]['total_image_count'] = $childTotal;
                $totalImages += $childTotal;

                continue;
            }

            if (str_starts_with((string) $child->getMimeType(), 'image/')) {
                $images[] = [
                    'id' => $child->getId(),
                    'name' => $child->getName(),
                    'mime_type' => $child->getMimeType(),
                    'size' => $child->getSize() !== null
                        ? (int) $child->getSize()
                        : null,
                    'modified_time' => $child->getModifiedTime(),
                    'folder_google_id' => $folderId,
                    'relative_directory' => $relativeDirectory,
                ];
                $directImages++;
                $totalImages++;

                continue;
            }

            $nonImageCount++;
        }

        return $totalImages;
    }

    /**
     * @return array<int, Google_Service_Drive_DriveFile>
     */
    protected function listAllChildren(string $folderId): array
    {
        $files = [];
        $pageToken = null;

        do {
            $result = $this->service->files->listFiles([
                'q' => "'{$folderId}' in parents and trashed = false",
                'fields' => 'nextPageToken,files(id,name,mimeType,size,modifiedTime)',
                'pageSize' => 1000,
                'pageToken' => $pageToken,
                'orderBy' => 'folder,name_natural',
            ]);

            array_push($files, ...$result->getFiles());
            $pageToken = $result->getNextPageToken();
        } while ($pageToken !== null);

        return $files;
    }

    /**
     * @param array<int, array<string, mixed>> $folders
     * @return array<string, DownloadFolder>
     */
    protected function persistFolders(
        DownloadData $download,
        array $folders,
    ): array {
        $records = [];

        DB::transaction(function () use (
            $download,
            $folders,
            &$records,
        ): void {
            foreach ($folders as $folder) {
                $parent = $records[$folder['parent_google_folder_id']]
                    ?? null;

                $record = DownloadFolder::query()->updateOrCreate(
                    [
                        'download_id' => $download->getKey(),
                        'google_folder_id' => $folder['google_folder_id'],
                    ],
                    [
                        'parent_id' => $parent?->getKey(),
                        'parent_google_folder_id' => $folder['parent_google_folder_id'],
                        'name' => $folder['name'],
                        'relative_path' => $folder['relative_path'],
                        'depth' => $folder['depth'],
                        'image_count' => $folder['image_count'],
                        'total_image_count' => $folder['total_image_count'],
                        'sort_order' => $folder['sort_order'],
                    ],
                );

                $records[$folder['google_folder_id']] = $record;
            }
        });

        return $records;
    }

    protected function sanitizeFolderSegment(
        string $name,
        string $folderId,
    ): string {
        $segment = preg_replace(
            '/[\/\\\\\x00-\x1F\x7F]+/u',
            '-',
            trim($name),
        );
        $segment = str_replace('..', '-', (string) $segment);
        $segment = trim((string) $segment, ". \t\n\r\0\x0B");

        if ($segment === '') {
            $segment = 'folder-' . Str::substr($folderId, 0, 8);
        }

        return Str::limit($segment, 100, '');
    }
}