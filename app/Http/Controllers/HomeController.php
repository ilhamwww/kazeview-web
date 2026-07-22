<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $data_konten = DB::table('contents')->orderBy('urut', 'asc')->get();
        $data_web = DB::table('website_settings')->get()->first();
        $data_links = $data_web ? json_decode($data_web->links, true) : null;
        $data_COLLECTIONS = DB::table('collections')->orderBy('urut', 'asc')->get();
        if ($data_links === null) {
            $data_links = [];
        }

        return view(view: 'landingpage.index')
            ->with('data_konten', $data_konten)
            ->with('data_web', $data_web)
            ->with('data_links', $data_links)
            ->with('galleryImages', $data_COLLECTIONS);
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

    public function index_preview()
    {
        $data_web = DB::table('website_settings')->get()->first();

        return view(view: 'landingpage.preview')
            ->with('data_web', $data_web);
    }

}
