<?php

namespace App\Http\Controllers;

use App\Models\AboutSetting;
use App\Models\ContactSetting;
use App\Models\Content;
use App\Models\ContentFilterCategory;
use App\Models\DownloadFolder;
use App\Models\ListDownloaded;
use App\Support\GoogleDriveFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index()
    {
        $data_web = DB::table('website_settings')->get()->first();
        $data_links = $data_web ? json_decode($data_web->links, true) : null;
        $data_COLLECTIONS = DB::table('collections')->orderBy('urut', 'asc')->get();
        if ($data_links === null) {
            $data_links = [];
        }

        return view(view: 'landingpage.index')
            ->with('data_web', $data_web)
            ->with('data_links', $data_links)
            ->with('galleryImages', $data_COLLECTIONS);
    }

    public function films()
    {
        $data_web = DB::table('website_settings')->first();
        $data_links = $data_web ? json_decode($data_web->links, true) : [];

        $films = DB::table('collections')
            ->where('is_active', true)
            ->where('media_type', 'FILM')
            ->where(function ($query): void {
                $query->whereNotNull('video')
                    ->orWhereNotNull('image');
            })
            ->orderByDesc('is_featured')
            ->orderBy('urut')
            ->get();

        return view('landingpage.films', [
            'data_web' => $data_web,
            'data_links' => $data_links ?? [],
            'films' => $films,
        ]);
    }

    public function index_product()
    {
        $hasCategoryMaster = Schema::hasTable(
            'content_filter_categories',
        ) && Schema::hasTable('content_content_filter_category');

        $data_konten = $hasCategoryMaster
            ? Content::query()
                ->with([
                    'filterCategories' => fn ($query) =>
                        $query
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name'),
                ])
                ->orderByDesc('urut')
                ->get()
            : DB::table('contents')
                ->orderByDesc('urut')
                ->get();

        $filterCategories = $hasCategoryMaster
            ? ContentFilterCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
            : collect();

        $data_web = DB::table('website_settings')->first();
        $data_links = $data_web
            ? json_decode($data_web->links, true)
            : [];

        return view('landingpage.product', [
            'data_konten' => $data_konten,
            'filterCategories' => $filterCategories,
            'data_web' => $data_web,
            'data_links' => $data_links ?? [],
        ]);
    }

    public function showPreview(Content $content, Request $request)
    {
        abort_unless($content->is_active, 404);

        if ($content->isVideoContent()) {
            abort_unless(
                GoogleDriveFolder::isGoogleDriveUrl($content->link),
                404,
            );

            return redirect()->away($content->link);
        }

        $data_web = DB::table('website_settings')->first();
        $data_links = $data_web ? json_decode($data_web->links, true) : [];

        $hasFolderSchema = Schema::hasTable('download_folders')
            && Schema::hasColumn('list_downloaded', 'download_folder_id')
            && Schema::hasColumn('list_downloaded', 'relative_path');

        $folders = $hasFolderSchema
            ? DownloadFolder::query()
                ->whereHas('download', function ($query) use ($content): void {
                    $query->where('id_content', $content->getKey());
                })
                ->orderBy('depth')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
            : collect();

        $selectedFolder = null;
        $folderId = $request->integer('folder');

        if ($folderId > 0) {
            abort_unless($hasFolderSchema, 404);

            $selectedFolder = $folders->firstWhere('id', $folderId);
            abort_unless($selectedFolder, 404);
        }

        $photosQuery = ListDownloaded::query()
            ->whereHas('download', function ($query) use ($content): void {
                $query->where('id_content', $content->getKey());
            })
            ->with('download:id,id_content,folder_name,id_folder');

        if ($hasFolderSchema) {
            $photosQuery->with(
                'folder:id,download_id,parent_id,name,relative_path,depth',
            );
        }

        if ($selectedFolder) {
            $folderIds = $folders
                ->filter(function (DownloadFolder $folder) use (
                    $selectedFolder,
                ): bool {
                    return $folder->getKey() === $selectedFolder->getKey()
                        || str_starts_with(
                            $folder->relative_path,
                            "{$selectedFolder->relative_path}/",
                        );
                })
                ->pluck('id');

            $photosQuery->whereIn('download_folder_id', $folderIds);
        }

        $photos = $photosQuery
            ->when(
                $hasFolderSchema,
                fn ($query) => $query->orderBy('download_folder_id'),
            )
            ->orderBy('file_name')
            ->paginate(48)
            ->withQueryString();

        $photos->setCollection(
            $photos->getCollection()
                ->filter(function (ListDownloaded $photo): bool {
                    return preg_match('/^[A-Za-z0-9_-]+$/', (string) $photo->id_folder) === 1
                        && preg_match('/^[A-Za-z0-9_-]+$/', (string) $photo->id_file_in_gd) === 1
                        && Storage::disk('public')->exists(
                            $photo->storagePath(),
                        );
                })
                ->map(function (ListDownloaded $photo) use (
                    $hasFolderSchema,
                ): ListDownloaded {
                    if (! $hasFolderSchema) {
                        $photo->setRelation('folder', null);
                    }

                    $photo->setAttribute(
                        'public_url',
                        Storage::disk('public')->url(
                            $photo->storagePath(),
                        ),
                    );

                    return $photo;
                })
                ->values(),
        );

        return view('landingpage.preview-detail', [
            'content' => $content,
            'photos' => $photos,
            'folders' => $folders,
            'selectedFolder' => $selectedFolder,
            'hasFolderSchema' => $hasFolderSchema,
            'data_web' => $data_web,
            'data_links' => $data_links ?? [],
        ]);
    }

    public function about()
    {
        $data_web = DB::table('website_settings')->first();
        $data_links = $data_web ? json_decode($data_web->links, true) : [];
        $about = AboutSetting::current();

        abort_unless($about->is_active, 404);

        $fallbackImages = DB::table('collections')
            ->whereNotNull('image')
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderBy('urut')
            ->limit(2)
            ->pluck('image')
            ->values();

        return view('landingpage.about', [
            'data_web' => $data_web,
            'data_links' => $data_links ?? [],
            'about' => $about,
            'fallbackHeroImage' => $fallbackImages->get(0),
            'fallbackStoryImage' => $fallbackImages->get(1) ?? $fallbackImages->get(0),
        ]);
    }

    public function contact()
    {
        $data_web = DB::table('website_settings')->first();
        $data_links = $data_web ? json_decode($data_web->links, true) : [];
        $contact = ContactSetting::current();

        abort_unless($contact->is_active, 404);

        if (! $contact->whatsapp && ! empty($data_web->wa)) {
            $contact->whatsapp = $data_web->wa;
        }

        if (empty($contact->social_links) && ! empty($data_links)) {
            $contact->social_links = $data_links;
        }

        $fallbackImage = DB::table('collections')
            ->whereNotNull('image')
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderBy('urut')
            ->value('image');

        return view('landingpage.contact', [
            'data_web' => $data_web,
            'data_links' => $data_links ?? [],
            'contact' => $contact,
            'fallbackImage' => $fallbackImage,
        ]);
    }

    public function index_preview()
    {
        $data_web = DB::table('website_settings')->get()->first();

        return view(view: 'landingpage.preview')
            ->with('data_web', $data_web);
    }

}
