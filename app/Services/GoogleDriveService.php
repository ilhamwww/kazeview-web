<?php

namespace App\Services;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class GoogleDriveService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/credentials/credentials.json'));

        // Impersonate user yang berbagi folder
        // $this->client->setSubject('jobs.kazeview@gmail.com');

        $this->client->addScope(Google_Service_Drive::DRIVE);
        $this->client->setAccessType('offline');

        $this->service = new Google_Service_Drive($this->client);
    }

    public function listImages($folderId)
    {
        $query = "'$folderId' in parents and mimeType contains 'image/' and trashed = false";

        $results = $this->service->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name, mimeType)',
        ]);

        $images = [];

        // $fileId = "1NbKPLFeXOSBow8N9GgFPKk-ALDYqK8Ds";
        // $response = $this->service->files->get($fileId, ['alt' => 'media']);
        // $content = $response->getBody()->getContents();
        // Storage::disk('public')->put("downloads/{$fileId}.jpg", $content);
        foreach ($results->getFiles() as $file) {

            $fileId = $file->getId();
            $response = $this->service->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();

            // $fileMeta = $this->service->files->get($fileId, ['fields' => 'mimeType']);
            // $mimeType = $fileMeta->getMimeType();
            $images[] = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                // 'url' => "https://drive.google.com/uc?export=view&id=" . $file->getId()
                // 'url'  => 'data:' . $mimeType . ';base64,' . base64_encode($content),
            ];
        }

        return $images;
    }



    public function download(Request $request)
    {
        return $this->downloadFolder(
            (string) $request->input('folder_id'),
            (int) $request->input('id_content'),
        );
    }

    public function downloadFolder(string $folderId, int $contentId): array
    {
        try {
            // Ambil data download_data (kalau ada)
            $downloadData = DB::table('download_data')->where('id_folder', $folderId)->where('id_content', $contentId)->first();

            // Query file dari Google Drive
            $query = "'$folderId' in parents and mimeType contains 'image/' and trashed = false";
            $results = $this->service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name, mimeType)',
            ]);
            $fileMeta = $this->service->files->get($folderId, ['fields' => 'name']);
            $folderName = $fileMeta->getName();
            // dd($folderName);
            $files = $results->getFiles();
            if (empty($files)) {
                return [
                    'status'  => 'error',
                    'message' => 'No image files found in this folder.',
                ];
            }

            // Kalau belum ada di download_data → buat record baru
            if (!$downloadData) {
                $downloadId = DB::table('download_data')->insertGetId([
                    'id_folder'  => $folderId,
                    'created_at' => now(),
                    'updated_at'=> now(),
                    'total_files' => count($files),
                    'id_content' => $contentId,
                    'folder_name' => $folderName,
                ]);
            } else {
                $downloadId = $downloadData->id;
                DB::table('download_data')->where('id', $downloadId)->update([
                    'updated_at' => now(),
                    'total_files' => count($files),
                ]);
            }

            // Ambil semua file_id yang sudah pernah di-download untuk folder ini
            $alreadyDownloadedIds = DB::table('list_downloaded')
                ->where('id_download', $downloadId)
                ->pluck('id_file_in_gd')
                ->toArray();

            $alreadyDownloadedSet = array_flip($alreadyDownloadedIds);

            $downloadedCount = 0;
            $skippedCount = 0;
            $insertList = [];

            foreach ($files as $file) {
                $fileId = $file->getId();

                // Skip kalau sudah ada
                if (isset($alreadyDownloadedSet[$fileId])) {
                    $skippedCount++;
                    continue;
                }

                // Download file langsung ke storage
                $response = $this->service->files->get($fileId, ['alt' => 'media']);
                Storage::disk('public')->put(
                    "downloads/{$folderId}/{$fileId}.jpg",
                    $response->getBody()->getContents()
                );

                // Siapkan batch insert
                $insertList[] = [
                    'id_download' => $downloadId,
                    'id_file_in_gd'   => $fileId,
                    'created_at'  => now(),
                    'file_name' => $file->getName(),
                    'id_folder' => $folderId,
                ];

                $downloadedCount++;
            }

            // Insert batch kalau ada file baru
            if (!empty($insertList)) {
                DB::table('list_downloaded')->insert($insertList);
            }

            if ($downloadedCount === 0) {
                return [
                    'status'  => 'info',
                    'message' => 'All files are already downloaded.',
                ];
            }

            return [
                'status'  => 'success',
                'message' => "{$downloadedCount} new files downloaded. {$skippedCount} files skipped.",
            ];
        } catch (\Exception $e) {
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }



    public function index(Request $request)
    {
    $folderPath = 'downloads/1-HqOyePcnIJSHk5mTP6O_F-AI-TvtLQx';

    $files = Storage::disk('public')->files($folderPath);

    $fileCount = count($files);
        return view('landingpage.preview_', []);
    }
}
