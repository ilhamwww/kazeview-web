<?php

namespace App\Http\Controllers;

use App\Models\AboutSetting;
use App\Models\ContactSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $data_konten = DB::table('contents')
            ->orderBy('urut', 'desc')
            ->get();
        $data_web = DB::table('website_settings')->get()->first();
        $data_links = $data_web ? json_decode($data_web->links, true) : null;

        if ($data_links === null) {
            $data_links = [];
        }

        return view(view: 'landingpage.product')
            ->with('data_konten', $data_konten)
            ->with('data_web', $data_web)
            ->with('data_links', $data_links);
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
