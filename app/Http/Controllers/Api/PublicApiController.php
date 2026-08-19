<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutSetting;
use App\Models\Collection;
use App\Models\CollectionCategory;
use App\Models\ContactSetting;
use App\Models\Content;
use App\Models\ContentFilterCategory;
use App\Models\DownloadFolder;
use App\Models\ListDownloaded;
use App\Models\WebsiteSetting;
use App\Services\MotorPhotoSearchService;
use App\Support\GoogleDriveFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PublicApiController extends Controller
{
    public function bootstrap(): JsonResponse
    {
        return $this->respond([
            'site' => $this->site(),
            'routes' => [
                'home' => route('api.public.home'),
                'films' => route('api.public.films'),
                'about' => route('api.public.about'),
                'contact' => route('api.public.contact'),
                'preview' => route('api.public.preview.index'),
            ],
        ]);
    }

    public function home(): JsonResponse
    {
        $collections = Collection::query()
            ->orderBy('urut')
            ->get()
            ->map(fn (Collection $collection): array => $this->collection(
                $collection,
            ))
            ->values();

        $categories = Schema::hasTable('collection_categories')
            ? CollectionCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (CollectionCategory $category): array => [
                    'id' => $category->getKey(),
                    'name' => $category->name,
                    'slug' => Str::slug($category->name),
                ])
                ->values()
            : collect();

        return $this->respond([
            'site' => $this->site(),
            'collections' => $collections,
            'categories' => $categories,
        ]);
    }

    public function films(): JsonResponse
    {
        $films = Collection::query()
            ->where('is_active', true)
            ->where('media_type', 'FILM')
            ->where(function ($query): void {
                $query->whereNotNull('video')
                    ->orWhereNotNull('image');
            })
            ->orderByDesc('is_featured')
            ->orderBy('urut')
            ->get()
            ->map(fn (Collection $collection): array => $this->collection(
                $collection,
            ))
            ->values();

        return $this->respond([
            'site' => $this->site(),
            'films' => $films,
        ]);
    }

    public function about(): JsonResponse
    {
        $about = AboutSetting::current();

        abort_unless($about->is_active, 404);

        $fallbackImages = Collection::query()
            ->whereNotNull('image')
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderBy('urut')
            ->limit(2)
            ->pluck('image');

        return $this->respond([
            'site' => $this->site(),
            'about' => [
                'eyebrow' => $about->eyebrow,
                'headline' => $about->headline,
                'intro' => $about->intro,
                'story_title' => $about->story_title,
                'story_body' => $about->story_body,
                'capabilities' => $about->capabilities
                    ?: AboutSetting::defaults()['capabilities'],
                'location' => $about->location,
                'established' => $about->established,
                'cta_title' => $about->cta_title,
                'cta_label' => $about->cta_label,
                'cta_url' => $about->cta_url,
                'hero_image_url' => $this->storageUrl(
                    $about->hero_image ?: $fallbackImages->get(0),
                ),
                'story_image_url' => $this->storageUrl(
                    $about->story_image
                        ?: $fallbackImages->get(1)
                        ?: $fallbackImages->get(0),
                ),
                'stats' => $about->stats ?? [],
            ],
        ]);
    }

    public function contact(): JsonResponse
    {
        $contact = ContactSetting::current();

        abort_unless($contact->is_active, 404);

        $site = $this->site();
        $fallbackImage = Collection::query()
            ->whereNotNull('image')
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderBy('urut')
            ->value('image');

        return $this->respond([
            'site' => $site,
            'contact' => [
                'eyebrow' => $contact->eyebrow,
                'headline' => $contact->headline,
                'intro' => $contact->intro,
                'email' => $contact->email,
                'whatsapp' => $contact->whatsapp ?: ($site['whatsapp'] ?? null),
                'whatsapp_url' => $contact->whatsappUrl(),
                'whatsapp_message' => $contact->whatsapp_message,
                'location' => $contact->location,
                'availability' => $contact->availability,
                'response_time' => $contact->response_time,
                'cta_label' => $contact->cta_label,
                'social_links' => $contact->social_links ?: ($site['links'] ?? []),
                'image_url' => $this->storageUrl(
                    $contact->image ?: $fallbackImage,
                ),
            ],
        ]);
    }

    public function preview(): JsonResponse
    {
        $hasCategories = Schema::hasTable('content_filter_categories')
            && Schema::hasTable('content_content_filter_category');

        $contents = Content::query()
            ->when(
                $hasCategories,
                fn ($query) => $query->with([
                    'filterCategories' => fn ($categories) =>
                        $categories
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name'),
                ]),
            )
            ->orderByDesc('urut')
            ->get()
            ->filter(fn (Content $content): bool => filled($content->image))
            ->map(fn (Content $content): array => $this->content($content))
            ->values();

        $categories = $hasCategories
            ? ContentFilterCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (ContentFilterCategory $category): array => [
                    'id' => $category->getKey(),
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->values()
            : collect();

        return $this->respond([
            'site' => $this->site(),
            'categories' => $categories,
            'contents' => $contents,
        ]);
    }

    public function previewDetail(
        Content $content,
        Request $request
    ): JsonResponse {
        abort_unless($content->is_active, 404);

        if ($content->isVideoContent()) {
            abort_unless(
                GoogleDriveFolder::isGoogleDriveUrl($content->link),
                404,
            );

            return $this->respond([
                'type' => 'redirect',
                'redirect_url' => $content->link,
                'content' => $this->content($content),
            ]);
        }

        $hasFolderSchema = Schema::hasTable('download_folders')
            && Schema::hasColumn('list_downloaded', 'download_folder_id')
            && Schema::hasColumn('list_downloaded', 'relative_path');

        $folders = $hasFolderSchema
            ? DownloadFolder::query()
                ->whereHas(
                    'download',
                    fn ($query) => $query->where(
                        'id_content',
                        $content->getKey(),
                    ),
                )
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
            ->whereHas(
                'download',
                fn ($query) => $query->where(
                    'id_content',
                    $content->getKey(),
                ),
            )
            ->with('download:id,id_content,folder_name,id_folder');

        if ($hasFolderSchema) {
            $photosQuery->with(
                'folder:id,download_id,parent_id,name,relative_path,depth',
            );
        }

        if ($selectedFolder) {
            $folderIds = $folders
                ->filter(
                    fn (DownloadFolder $folder): bool =>
                        $folder->getKey() === $selectedFolder->getKey()
                        || str_starts_with(
                            $folder->relative_path,
                            "{$selectedFolder->relative_path}/",
                        ),
                )
                ->pluck('id');

            $photosQuery->whereIn('download_folder_id', $folderIds);
        }

        $brandPhotoIds = [];
        $hasMotorIndex = Schema::hasTable('photo_motor_search_indexes');
        $facetPhotos = (clone $photosQuery)
            ->when(
                $hasMotorIndex,
                fn ($query) => $query->with(
                    'motorSearchIndex:id,list_downloaded_id,status,descriptor',
                ),
            )
            ->get(['id']);

        foreach ($facetPhotos as $facetPhoto) {
            $brand = $hasMotorIndex
                ? $this->canonicalMotorcycleBrand(
                    $facetPhoto->motorSearchIndex,
                )
                : 'unknown';
            $brandPhotoIds[$brand][] = $facetPhoto->getKey();
        }

        $brandFilters = collect($brandPhotoIds)
            ->map(fn (array $ids, string $brand): array => [
                'slug' => $brand,
                'label' => Str::upper($brand),
                'count' => count($ids),
            ])
            ->sortBy(fn (array $filter): string =>
                $filter['slug'] === 'unknown'
                    ? '1'
                    : '0'.$filter['label']
            )
            ->values();

        $selectedBrand = Str::slug(
            mb_strtolower(trim((string) $request->query('brand', 'all'))),
        ) ?: 'all';

        if ($selectedBrand !== 'all') {
            abort_unless(isset($brandPhotoIds[$selectedBrand]), 404);
            $photosQuery->whereIn('id', $brandPhotoIds[$selectedBrand]);
        }

        $photos = $photosQuery
            ->when(
                $hasFolderSchema,
                fn ($query) => $query->orderBy('download_folder_id'),
            )
            ->orderBy('file_name')
            ->paginate(
                48,
                ['*'],
                'page',
                max(1, $request->integer('page', 1))
            );

        $photoData = $photos->getCollection()
            ->filter(function (ListDownloaded $photo): bool {
                return preg_match(
                    '/^[A-Za-z0-9_-]+$/',
                    (string) $photo->id_folder,
                ) === 1
                    && preg_match(
                        '/^[A-Za-z0-9_-]+$/',
                        (string) $photo->id_file_in_gd,
                    ) === 1
                    && Storage::disk('public')->exists(
                        $photo->storagePath(),
                    );
            })
            ->map(fn (ListDownloaded $photo): array => [
                'id' => $photo->getKey(),
                'name' => $photo->file_name,
                'url' => Storage::disk('public')->url(
                    $photo->storagePath(),
                ),
                'folder' => $hasFolderSchema && $photo->folder
                    ? [
                        'id' => $photo->folder->getKey(),
                        'name' => $photo->folder->name,
                        'relative_path' => $photo->folder->relative_path,
                    ]
                    : null,
            ])
            ->values();

        return $this->respond([
            'type' => 'gallery',
            'site' => $this->site(),
            'content' => $this->content($content),
            'folders' => $folders->map(
                fn (DownloadFolder $folder): array => [
                    'id' => $folder->getKey(),
                    'parent_id' => $folder->parent_id,
                    'name' => $folder->name,
                    'relative_path' => $folder->relative_path,
                    'depth' => $folder->depth,
                    'image_count' => $folder->image_count,
                    'total_image_count' => $folder->total_image_count,
                ],
            )->values(),
            'selected_folder_id' => $selectedFolder
                ? $selectedFolder->getKey()
                : null,
            'selected_brand' => $selectedBrand,
            'brand_filters' => [
                [
                    'slug' => 'all',
                    'label' => 'ALL PHOTOS',
                    'count' => $facetPhotos->count(),
                ],
                ...$brandFilters->all(),
            ],
            'photos' => [
                'data' => $photoData,
                'current_page' => $photos->currentPage(),
                'last_page' => $photos->lastPage(),
                'per_page' => $photos->perPage(),
                'total' => $photos->total(),
            ],
        ]);
    }

    public function searchMotorPhotos(
        Content $content,
        Request $request,
        MotorPhotoSearchService $searchService,
    ): JsonResponse {
        abort_unless(
            $content->is_active && $content->isPhotoContent(),
            404,
        );

        if (! $content->ai_photo_search_enabled) {
            return response()->json([
                'message' => 'AI Photo Search tidak aktif untuk Content ini.',
            ], 409)->header('Cache-Control', 'no-store');
        }

        $validated = $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:8192',
                'dimensions:min_width=128,min_height=128,max_width=12000,max_height=12000',
            ],
        ]);

        try {
            $result = $searchService->search(
                $content,
                $validated['photo'],
            );
        } catch (Throwable $exception) {
            $reference = (string) Str::uuid();

            Log::error('AI Photo Search request failed.', [
                'reference' => $reference,
                'content_id' => $content->getKey(),
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Pencarian foto sedang tidak tersedia. Silakan coba kembali.',
                'reference' => $reference,
            ], 503)->header('Cache-Control', 'no-store');
        }

        return response()->json([
            'data' => [
                'content_id' => $content->getKey(),
                'label' => 'Possible matches',
                'query_analysis' => $result['descriptor'],
                'debug' => [
                    'vision_model' => (string) config('services.ninerouter.vision_model'),
                    'embedding_model' => (string) config('services.ninerouter.embedding_model'),
                    'prompt_version' => (string) config('services.ninerouter.prompt_version'),
                    'score_weights' => [
                        'motor_identity_multiplier' => 0.16,
                        'helmet_max_bonus' => 0.06,
                    ],
                ],
                'matches' => $result['matches'],
            ],
        ])->header('Cache-Control', 'no-store');
    }

    private function canonicalMotorcycleBrand(mixed $index): string
    {
        if (! $index || $index->status !== 'indexed') {
            return 'unknown';
        }

        $descriptor = is_array($index->descriptor)
            ? $index->descriptor
            : [];

        if (($descriptor['motorcycle_present'] ?? true) === false) {
            return 'unknown';
        }

        $brand = Str::of((string) ($descriptor['brand_guess'] ?? ''))
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();

        if ($brand === '' || in_array($brand, [
            'unknown',
            'uncertain',
            'unidentified',
            'n a',
            'none',
        ], true)) {
            return 'unknown';
        }

        $aliases = [
            'bmw motorrad' => 'bmw',
            'harley davidson' => 'harley-davidson',
            'royal enfield' => 'royal-enfield',
            'cf moto' => 'cfmoto',
            'mv agusta' => 'mv-agusta',
        ];

        foreach ($aliases as $alias => $canonical) {
            if ($brand === $alias || str_starts_with($brand, $alias.' ')) {
                return $canonical;
            }
        }

        $knownBrands = [
            'aprilia',
            'benelli',
            'bmw',
            'cfmoto',
            'ducati',
            'gasgas',
            'harley-davidson',
            'hero',
            'honda',
            'husqvarna',
            'indian',
            'kawasaki',
            'ktm',
            'kymco',
            'moto-guzzi',
            'mv-agusta',
            'piaggio',
            'royal-enfield',
            'suzuki',
            'triumph',
            'vespa',
            'victory',
            'yamaha',
        ];

        $slug = Str::slug($brand);

        foreach ($knownBrands as $knownBrand) {
            if ($slug === $knownBrand || str_starts_with($slug, $knownBrand.'-')) {
                return $knownBrand;
            }
        }

        $genericSuffixes = [
            ' motorcycles',
            ' motorcycle',
            ' motorbikes',
            ' motorbike',
            ' motors',
            ' motor',
        ];

        foreach ($genericSuffixes as $suffix) {
            if (str_ends_with($brand, $suffix)) {
                $brand = trim(substr($brand, 0, -strlen($suffix)));
                break;
            }
        }

        return Str::slug($brand) ?: 'unknown';
    }

    private function site(): array
    {
        $settings = WebsiteSetting::query()->first();
        $links = $settings ? ($settings->links ?? []) : [];
        $seo = $settings
            ? $settings->seo
            : WebsiteSetting::seoDefaults();

        return [
            'name' => $settings && $settings->name
                ? $settings->name
                : 'KAZEVIEW',
            'description' => $settings ? $settings->description : null,
            'logo_url' => $this->storageUrl(
                $settings ? $settings->logo : null,
            ),
            'hero_image_url' => $this->storageUrl(
                $settings ? $settings->hero_image : null,
            ),
            'whatsapp' => $settings ? $settings->wa : null,
            'links' => is_array($links) ? $links : [],
            'seo' => [
                'title' => $seo['title'] ?? null,
                'description' => $seo['description'] ?? null,
                'keywords' => $seo['keywords'] ?? null,
                'canonical_url' => $seo['canonical_url'] ?? null,
                'robots' => $seo['robots'] ?? 'index, follow',
                'author' => $seo['author'] ?? null,
                'theme_color' => $seo['theme_color'] ?? '#050506',
                'og_title' => $seo['og_title'] ?? null,
                'og_description' => $seo['og_description'] ?? null,
                'twitter_card' => $seo['twitter_card'] ?? 'summary_large_image',
                'twitter_title' => $seo['twitter_title'] ?? null,
                'twitter_description' => $seo['twitter_description'] ?? null,
                'google_verification' => $seo['google_verification'] ?? null,
                'bing_verification' => $seo['bing_verification'] ?? null,
                'same_as' => $seo['same_as'] ?? [],
                'og_image_url' => $this->storageUrl(
                    $settings ? $settings->seo_image : null,
                ),
            ],
        ];
    }

    private function collection(Collection $collection): array
    {
        return [
            'id' => $collection->getKey(),
            'title' => $collection->title,
            'subtitle' => $collection->subtitle,
            'category' => $collection->category,
            'media_type' => $collection->media_type,
            'image_url' => $this->storageUrl($collection->image),
            'video_url' => $this->publicUrl($collection->video),
            'link' => $this->publicUrl($collection->link),
            'duration' => $collection->duration,
            'project_year' => $collection->project_year,
            'image_position' => $collection->image_position ?: '50% 50%',
            'is_active' => (bool) $collection->is_active,
            'is_featured' => (bool) $collection->is_featured,
            'sort_order' => $collection->urut,
        ];
    }

    private function content(Content $content): array
    {
        $mediaType = $content->isVideoContent() ? 'VIDEO' : 'FOTO';
        $categories = $content->relationLoaded('filterCategories')
            ? $content->filterCategories->map(
                fn (ContentFilterCategory $category): array => [
                    'id' => $category->getKey(),
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
            )->values()
            : collect();

        return [
            'id' => $content->getKey(),
            'title' => $content->title,
            'body' => $content->body,
            'location' => $content->event_location,
            'event_date' => $content->event_date
                ? $content->event_date->toDateString()
                : null,
            'media_type' => $mediaType,
            'preview_type' => $mediaType === 'VIDEO'
                ? 'VIDEO'
                : ($content->preview_type ?: 'PHOTOGRAPHY'),
            'film_duration' => $content->film_duration,
            'image_url' => $this->storageUrl($content->image),
            'image_position' => $content->image_position ?: '50% 50%',
            'is_active' => (bool) $content->is_active,
            'is_new' => (bool) $content->is_new,
            'is_price_enabled' => (bool) $content->is_price_enabled,
            'ai_photo_search_enabled' => (bool) $content->ai_photo_search_enabled,
            'ai_photo_search_status' => $content->ai_index_status,
            'price' => $content->is_price_enabled
                ? (float) $content->price
                : null,
            'redirect_url' => $mediaType === 'VIDEO'
                && GoogleDriveFolder::isGoogleDriveUrl($content->link)
                    ? $content->link
                    : null,
            'categories' => $categories,
        ];
    }

    private function storageUrl(?string $path): ?string
    {
        return filled($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    private function publicUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return str_starts_with($path, 'https://')
                ? $path
                : null;
        }

        return Storage::disk('public')->url($path);
    }

    private function respond(array $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
        ])->header('Cache-Control', 'public, max-age=30, s-maxage=60');
    }
}