<?php

namespace App\Http\Controllers;

use App\Models\AboutSetting;
use App\Models\CollectionCategory;
use App\Models\ContactSetting;
use App\Models\Content;
use App\Models\ContentFilterCategory;
use App\Models\ListDownloaded;
use App\Support\GoogleDriveFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index()
    {
        $data_web = DB::table('website_settings')->get()->first();
        $data_links = $data_web ? json_decode($data_web->links, true) : null;
        $data_COLLECTIONS = DB::table('collections')->orderBy('urut', 'asc')->get();
        $collectionCategories = Schema::hasTable('collection_categories')
            ? CollectionCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        if ($data_links === null) {
            $data_links = [];
        }

        return view(view: 'landingpage.index')
            ->with('data_web', $data_web)
            ->with('data_links', $data_links)
            ->with('collectionCategories', $collectionCategories)
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

        $photosQuery = ListDownloaded::query()
            ->whereHas('download', function ($query) use ($content): void {
                $query->where('id_content', $content->getKey());
            });

        $brandPhotoIds = [];
        $categoryPhotoIds = [];
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
            $category = $hasMotorIndex
                ? $this->canonicalMotorcycleCategory(
                    $facetPhoto->motorSearchIndex,
                )
                : 'unknown';

            $brandPhotoIds[$brand][] = $facetPhoto->getKey();
            $categoryPhotoIds[$category][] = $facetPhoto->getKey();
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

        $categoryFilters = collect($categoryPhotoIds)
            ->map(fn (array $ids, string $category): array => [
                'slug' => $category,
                'label' => Str::upper(str_replace('-', ' ', $category)),
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

        $selectedCategory = Str::slug(
            mb_strtolower(trim((string) $request->query('category', 'all'))),
        ) ?: 'all';

        if ($selectedBrand !== 'all') {
            abort_unless(isset($brandPhotoIds[$selectedBrand]), 404);
            $photosQuery->whereIn('id', $brandPhotoIds[$selectedBrand]);
        }

        if ($selectedCategory !== 'all') {
            abort_unless(isset($categoryPhotoIds[$selectedCategory]), 404);
            $photosQuery->whereIn(
                'id',
                $categoryPhotoIds[$selectedCategory],
            );
        }

        $photos = $photosQuery
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
                ->map(function (ListDownloaded $photo): ListDownloaded {
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

        if ($request->boolean('infinite')) {
            return response()->json([
                'html' => view(
                    'landingpage.partials.preview-photo-batch',
                    ['photos' => $photos],
                )->render(),
                'next_url' => $photos->hasMorePages()
                    ? $photos->appends(['infinite' => 1])->nextPageUrl()
                    : null,
                'loaded' => $photos->count(),
                'from' => $photos->firstItem(),
                'to' => $photos->lastItem(),
                'total' => $photos->total(),
            ]);
        }

        return view('landingpage.preview-detail', [
            'content' => $content,
            'photos' => $photos,
            'selectedBrand' => $selectedBrand,
            'selectedCategory' => $selectedCategory,
            'brandFilters' => collect([
                [
                    'slug' => 'all',
                    'label' => 'ALL BRANDS',
                    'count' => $facetPhotos->count(),
                ],
                ...$brandFilters->all(),
            ]),
            'categoryFilters' => collect([
                [
                    'slug' => 'all',
                    'label' => 'ALL CATEGORIES',
                    'count' => $facetPhotos->count(),
                ],
                ...$categoryFilters->all(),
            ]),
            'data_web' => $data_web,
            'data_links' => $data_links ?? [],
        ]);
    }

    private function canonicalMotorcycleCategory(mixed $index): string
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

        $category = Str::of((string) ($descriptor['category'] ?? ''))
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();

        if ($category === '' || in_array($category, [
            'unknown',
            'uncertain',
            'unidentified',
            'n a',
            'none',
        ], true)) {
            return 'unknown';
        }

        $aliases = [
            'sports' => 'sport',
            'sport bike' => 'sport',
            'sportbike' => 'sport',
            'super sport' => 'supersport',
            'superbike' => 'supersport',
            'adventure bike' => 'adventure',
            'adventure touring' => 'adventure',
            'dual sport' => 'dual-sport',
            'dual purpose' => 'dual-sport',
            'off road' => 'off-road',
            'dirt bike' => 'off-road',
            'street bike' => 'street',
            'standard bike' => 'standard',
            'naked bike' => 'naked',
            'touring bike' => 'touring',
            'cafe racer' => 'cafe-racer',
            'under bone' => 'underbone',
        ];

        return $aliases[$category] ?? Str::slug($category) ?: 'unknown';
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
            'aprilia', 'benelli', 'bmw', 'cfmoto', 'ducati', 'gasgas',
            'harley-davidson', 'hero', 'honda', 'husqvarna', 'indian',
            'kawasaki', 'ktm', 'kymco', 'moto-guzzi', 'mv-agusta',
            'piaggio', 'royal-enfield', 'suzuki', 'triumph', 'vespa',
            'victory', 'yamaha',
        ];

        $slug = Str::slug($brand);

        foreach ($knownBrands as $knownBrand) {
            if ($slug === $knownBrand || str_starts_with($slug, $knownBrand.'-')) {
                return $knownBrand;
            }
        }

        foreach ([
            ' motorcycles',
            ' motorcycle',
            ' motorbikes',
            ' motorbike',
            ' motors',
            ' motor',
        ] as $suffix) {
            if (str_ends_with($brand, $suffix)) {
                $brand = trim(substr($brand, 0, -strlen($suffix)));
                break;
            }
        }

        return Str::slug($brand) ?: 'unknown';
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
