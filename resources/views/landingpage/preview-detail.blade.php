@extends('layouts.app')

@section('title', strtoupper($content->title) . ' — KAZEVIEW')
@section('meta_description', 'Preview galeri foto event ' . $content->title . ' oleh KAZEVIEW.')

@php
    $eventTitle = strtoupper(trim((string) $content->title));
    $eventLocation = strtoupper(trim((string) ($content->event_location ?: 'LOCATION TBA')));
    $eventDate = $content->event_date?->format('d M Y') ?? '';
    $eventType = in_array($content->preview_type, ['PHOTOGRAPHY', 'PHOTO + FILM'], true)
        ? $content->preview_type
        : 'PHOTOGRAPHY';
    $eventPrice = $content->is_price_enabled && is_numeric($content->price)
        ? 'Rp ' . number_format((float) $content->price, 0, ',', '.') . ' / PHOTO'
        : 'FREE PREVIEW';
    $coverImage = $content->image
        ? asset('storage/' . $content->image)
        : asset('KAZE_icon.png');
    $watermarkPositions = [
        'top-left',
        'top-center',
        'top-right',
        'center-left',
        'center',
        'center-right',
        'bottom-left',
        'bottom-center',
        'bottom-right',
    ];
    $configuredWatermarkPosition = data_get(
        $data_web,
        'lightbox_watermark_position',
        'center',
    );
    $watermarkPosition = in_array(
        $configuredWatermarkPosition,
        $watermarkPositions,
        true,
    )
        ? $configuredWatermarkPosition
        : 'center';
    $watermarkSize = min(
        90,
        max(
            5,
            (int) (
                data_get($data_web, 'lightbox_watermark_size')
                ?: 30
            ),
        ),
    );
    $watermarkOpacity = min(
        100,
        max(
            5,
            (int) (
                data_get($data_web, 'lightbox_watermark_opacity')
                ?: 65
            ),
        ),
    );
    $watermarkImage = data_get(
        $data_web,
        'lightbox_watermark_image',
    );
    $watermarkEnabled = (bool) data_get(
        $data_web,
        'lightbox_watermark_enabled',
        false,
    ) && filled($watermarkImage);
    $watermarkUrl = $watermarkEnabled
        ? asset('storage/' . $watermarkImage)
        : null;

    $whatsAppNumber = preg_replace('/\D+/', '', (string) ($data_web->wa ?? ''));
    if ($whatsAppNumber !== '' && str_starts_with($whatsAppNumber, '0')) {
        $whatsAppNumber = '62' . substr($whatsAppNumber, 1);
    } elseif ($whatsAppNumber !== '' && !str_starts_with($whatsAppNumber, '62')) {
        $whatsAppNumber = '62' . $whatsAppNumber;
    }
    $waText = rawurlencode("Halo KAZEVIEW, saya ingin konfirmasi pembayaran QRIS untuk event: {$eventTitle}");
    $helpUrl = $whatsAppNumber !== '' ? 'https://wa.me/' . $whatsAppNumber . '?text=' . $waText : '#contact';
@endphp

@section('preloads')
    <link rel="preload" href="{{ $coverImage }}" as="image" fetchpriority="high">
@endsection

@section('styles')
    <style>
        .payment-modal { z-index: 2000; }
        .payment-utilities { display:flex; flex-wrap:wrap; align-items:center; gap:16px; margin-top:1.5rem; }
        .qris-button { display:inline-flex; height:46px; padding:0 24px; align-items:center; justify-content:center; gap:10px; cursor:pointer; background:rgba(0,0,0,.45); border:1px solid rgba(255,255,255,.32); border-radius:3px; color:#fff; font:700 .75rem/1 Inter,sans-serif; letter-spacing:.08em; transition:border-color .2s ease,background-color .2s ease; }
        .qris-button:hover { background:var(--color-accent-soft, rgba(255,42,42,.15)); border-color:var(--color-accent, #ff2a2a); color:#fff; }
        .qris-button--toolbar { height:40px; padding:0 18px; font-size:.72rem; }
        .qris-button--lightbox { height:36px; padding:0 14px; font-size:.68rem; border-color:rgba(255,255,255,.35); background:rgba(0,0,0,.5); }
        .help-link { display:inline-flex; min-height:44px; align-items:center; gap:8px; color:inherit; text-decoration:none; font:700 .75rem/1 Inter,sans-serif; letter-spacing:.08em; transition:color .2s ease; }
        .help-link:hover { color:var(--color-accent, #ff2a2a); }
        .whatsapp-icon { display:block; width:18px; height:18px; fill:currentColor; flex:0 0 18px; }
        .icon-outline { width:18px; height:18px; flex:0 0 auto; fill:none; stroke:currentColor; stroke-linecap:round; stroke-linejoin:round; stroke-width:1.6; }
        .gallery-detail { padding: clamp(2rem, 5vw, 5rem) var(--page-gutter, 5vw) 6rem; }
        .gallery-back { display:inline-flex; align-items:center; gap:.6rem; color:inherit; text-decoration:none; font:700 .78rem/1 Inter,sans-serif; letter-spacing:.12em; margin-bottom:2rem; }
        .gallery-back:hover { color:var(--color-accent); }
        .gallery-hero { position:relative; min-height:min(68vh,680px); display:flex; align-items:flex-end; overflow:hidden; background:#111; }
        .gallery-hero__image,.gallery-hero__shade { position:absolute; inset:0; width:100%; height:100%; }
        .gallery-hero__image { object-fit:cover; }
        .gallery-hero__shade { background:linear-gradient(180deg,rgba(0,0,0,.08),rgba(0,0,0,.82)); }
        .gallery-hero__content { position:relative; z-index:1; color:#fff; padding:clamp(1.5rem,5vw,4.5rem); max-width:980px; }
        .gallery-kicker { color:var(--color-accent); font:800 .75rem/1 Inter,sans-serif; letter-spacing:.16em; }
        .gallery-title { margin:.75rem 0 1rem; font:800 clamp(2.3rem,7vw,6.5rem)/.9 "Inter Tight",sans-serif; letter-spacing:-.055em; }
        .gallery-meta { display:flex; flex-wrap:wrap; gap:.65rem 1.5rem; font:600 .8rem/1.4 Inter,sans-serif; letter-spacing:.08em; }
        .gallery-description { max-width:720px; margin:1.3rem 0 0; color:rgba(255,255,255,.76); line-height:1.7; }
        .ai-photo-search { margin:clamp(2rem,5vw,4.5rem) 0 0; padding:clamp(1.4rem,4vw,3.5rem); border:1px solid rgba(127,127,127,.32); background:radial-gradient(circle at 100% 0,var(--color-accent-soft),transparent 42%),#0c0c0d; }
        .ai-photo-search__header { max-width:760px; }
        .ai-photo-search__header h2 { margin:.7rem 0 1rem; font:800 clamp(2rem,5vw,4.5rem)/.92 "Inter Tight",sans-serif; letter-spacing:-.05em; }
        .ai-photo-search__header > p:last-child { margin:0; opacity:.68; line-height:1.65; }
        .ai-photo-search__form { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:center; margin-top:1.75rem; }
        .ai-photo-search__picker { min-width:0; min-height:54px; display:flex; align-items:center; padding:0 1rem; overflow:hidden; border:1px solid rgba(127,127,127,.42); cursor:pointer; font:700 .72rem/1 Inter,sans-serif; letter-spacing:.09em; }
        .ai-photo-search__picker:hover { border-color:var(--color-accent); }
        .ai-photo-search__picker span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ai-photo-search__picker input { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }
        .ai-photo-search__submit { min-height:54px; padding:0 1.5rem; border:1px solid var(--color-accent); color:#fff; background:var(--color-accent); cursor:pointer; font:800 .72rem/1 Inter,sans-serif; letter-spacing:.09em; }
        .ai-photo-search__submit:hover:not(:disabled) { color:#fff; background:transparent; }
        .ai-photo-search__submit:disabled { opacity:.42; cursor:not-allowed; }
        .ai-photo-search__preview { display:none; width:min(100%,320px); max-height:280px; margin-top:1rem; object-fit:contain; background:#050506; }
        .ai-photo-search__preview.is-visible { display:block; }
        .ai-photo-search__privacy { margin:.8rem 0 0; opacity:.5; font-size:.75rem; line-height:1.5; }
        .ai-photo-search__brand { display:none; margin:1rem 0 0; color:var(--color-accent); font:800 clamp(1.35rem,3vw,2.25rem)/1 "Inter Tight",sans-serif; letter-spacing:-.025em; text-transform:uppercase; }
        .ai-photo-search__brand.is-visible { display:block; }
        .ai-photo-search__message { display:none; margin:1rem 0 0; padding:.85rem 1rem; border-left:3px solid var(--color-accent); background:var(--color-accent-soft); line-height:1.5; }
        .ai-photo-search__message.is-visible { display:block; }
        .ai-photo-search__message.is-error { color:var(--color-accent-hover); border-left-color:var(--color-accent); background:var(--color-accent-soft); }
        .ai-photo-search__results { display:none; margin-top:2.5rem; padding-top:2rem; border-top:1px solid rgba(127,127,127,.3); }
        .ai-photo-search__results.is-visible { display:block; }
        .ai-photo-search__results-heading { display:flex; justify-content:space-between; align-items:end; gap:1rem; margin-bottom:1rem; }
        .ai-photo-search__results-heading h3 { margin:0; font:800 clamp(1.5rem,4vw,2.8rem)/1 "Inter Tight",sans-serif; letter-spacing:-.04em; }
        .ai-photo-search__results-heading span { opacity:.55; font:700 .68rem/1 Inter,sans-serif; letter-spacing:.1em; }
        .ai-photo-search__grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:clamp(.5rem,1.2vw,1rem); }
        .ai-photo-search__match { position:relative; display:block; overflow:hidden; aspect-ratio:1/1; color:#fff; background:#151515; }
        .ai-photo-search__match img { width:100%; height:100%; object-fit:cover; transition:transform .45s ease; }
        .ai-photo-search__match:hover img { transform:scale(1.035); }
        .ai-photo-search__match-meta { position:absolute; right:0; bottom:0; left:0; display:flex; justify-content:space-between; gap:.5rem; padding:.7rem; background:linear-gradient(transparent,rgba(0,0,0,.9)); font:700 .62rem/1.3 Inter,sans-serif; letter-spacing:.06em; text-transform:uppercase; }
        .gallery-filter-group { margin:2rem 0 0; }
        .gallery-filter-group + .gallery-filter-group { margin-top:1.25rem; }
        .gallery-filter-label { margin:0 0 .7rem; opacity:.55; font:800 .66rem/1 Inter,sans-serif; letter-spacing:.14em; text-transform:uppercase; }
        .gallery-brands { display:flex; max-width:100%; gap:.5rem; padding-bottom:.4rem; overflow-x:auto; scrollbar-color:rgba(127,127,127,.42) transparent; scrollbar-width:thin; }
        .gallery-brand { display:inline-flex; min-width:max-content; min-height:42px; align-items:center; justify-content:center; gap:.55rem; padding:.7rem 1rem; border:1px solid rgba(127,127,127,.32); color:inherit; text-decoration:none; font:800 .72rem/1 Inter,sans-serif; letter-spacing:.09em; text-transform:uppercase; transition:border-color .2s ease,background-color .2s ease,color .2s ease; }
        .gallery-brand strong { color:rgba(255,255,255,.52); font:inherit; letter-spacing:.04em; transition:color .2s ease; }
        .gallery-brand:hover,.gallery-brand:focus-visible { border-color:var(--color-accent); outline:none; }
        .gallery-brand.is-active { border-color:var(--color-accent); background:var(--color-accent); color:#fff; }
        .gallery-brand.is-active strong { color:#fff; }
        [data-gallery-section].is-filtering .photo-grid { opacity:.38; pointer-events:none; }
        [data-gallery-section].is-filtering .gallery-brand { pointer-events:none; }
        .gallery-toolbar { display:flex; justify-content:space-between; align-items:end; gap:1rem; margin:2rem 0 1.5rem; border-bottom:1px solid rgba(127,127,127,.32); padding-bottom:1rem; }
        .gallery-toolbar h2 { margin:0; font:800 clamp(1.7rem,4vw,3.2rem)/1 "Inter Tight",sans-serif; letter-spacing:-.04em; }
        .gallery-toolbar p { margin:0; font:700 .75rem/1 Inter,sans-serif; letter-spacing:.12em; opacity:.6; }
        .photo-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:clamp(.5rem,1.2vw,1rem); }
        .photo-card { position:relative; border:0; padding:0; overflow:hidden; background:#151515; cursor:zoom-in; aspect-ratio:1/1; }
        .photo-card img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .45s ease,opacity .25s; }
        .photo-card:hover img,.photo-card:focus-visible img { transform:scale(1.035); }
        .photo-card__number { position:absolute; right:.65rem; bottom:.65rem; padding:.4rem .55rem; color:#fff; background:rgba(0,0,0,.72); font:700 .65rem/1 Inter,sans-serif; letter-spacing:.08em; }
        .gallery-empty { padding:5rem 1.5rem; text-align:center; border:1px solid rgba(127,127,127,.28); }
        .gallery-empty h2 { font:800 clamp(1.8rem,5vw,3.5rem)/1 "Inter Tight",sans-serif; margin:0 0 1rem; }
        .gallery-empty p { opacity:.65; margin:0; }
        .gallery-load-more { display:grid; min-height:110px; margin-top:2rem; padding-top:1.5rem; place-items:center; border-top:1px solid rgba(127,127,127,.28); text-align:center; }
        .gallery-load-more__status { margin:0; opacity:.65; font:700 .72rem/1.5 Inter,sans-serif; letter-spacing:.1em; text-transform:uppercase; }
        .gallery-load-more__status::before { display:inline-block; width:8px; height:8px; margin-right:.7rem; content:""; background:var(--color-accent); border-radius:50%; }
        .gallery-load-more.is-loading .gallery-load-more__status::before { animation:gallery-load-pulse .8s ease-in-out infinite alternate; }
        .gallery-load-more.is-complete .gallery-load-more__status::before { opacity:.35; }
        .gallery-load-more__retry { display:none; min-height:42px; margin-top:1rem; padding:0 1rem; border:1px solid var(--color-accent); color:#fff; background:transparent; cursor:pointer; font:800 .72rem/1 Inter,sans-serif; letter-spacing:.09em; }
        .gallery-load-more.has-error .gallery-load-more__retry { display:inline-flex; align-items:center; justify-content:center; }
        @keyframes gallery-load-pulse { to { opacity:.25; transform:scale(.7); } }
        .photo-lightbox { position:fixed; inset:0; z-index:1000; display:none; background:rgba(0,0,0,.94); color:#fff; }
        .photo-lightbox.is-open { display:grid; grid-template-rows:auto 1fr auto; }
        .photo-lightbox__top { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.25rem; }
        .photo-lightbox__close,.photo-lightbox__nav { border:1px solid rgba(255,255,255,.35); background:rgba(0,0,0,.35); color:#fff; cursor:pointer; }
        .photo-lightbox__close { width:44px;height:44px;font-size:1.5rem; }
        .photo-lightbox__stage { position:relative; min-height:0; display:flex; align-items:center; justify-content:center; padding:0 4rem; }
        .photo-lightbox__frame { position:relative; display:inline-flex; max-width:100%; max-height:calc(100vh - 150px); align-items:center; justify-content:center; overflow:hidden; user-select:none; -webkit-user-select:none; -webkit-touch-callout:none; }
        .photo-lightbox__image { display:block; max-width:100%; max-height:calc(100vh - 150px); object-fit:contain; user-select:none; -webkit-user-select:none; -webkit-touch-callout:none; pointer-events:none; }
        .photo-lightbox__shield { position:absolute; inset:0; z-index:3; background:transparent; user-select:none; -webkit-user-select:none; -webkit-touch-callout:none; }
        .photo-lightbox__nav { position:absolute; top:50%; transform:translateY(-50%); width:48px;height:64px;font-size:1.5rem; z-index:4; }
        .photo-lightbox__nav--prev { left:.75rem; } .photo-lightbox__nav--next { right:.75rem; }
        .photo-lightbox__caption { padding:1rem 1.25rem; text-align:center; font:.75rem/1.4 Inter,sans-serif; letter-spacing:.08em; }
        @media(max-width:900px){.photo-grid,.ai-photo-search__grid{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:640px){.gallery-detail{padding-left:1rem;padding-right:1rem}.gallery-hero{min-height:58vh}.photo-grid,.ai-photo-search__grid{grid-template-columns:repeat(2,1fr)}.ai-photo-search__form{grid-template-columns:1fr}.ai-photo-search__submit{width:100%}.ai-photo-search__results-heading{align-items:start;flex-direction:column}.gallery-toolbar{align-items:start;flex-direction:column}.photo-lightbox__stage{padding:0 2.75rem}}
    </style>
@endsection

@section('content')
    <main class="gallery-detail">
        <a class="gallery-back" href="{{ route('home.index_product') }}" aria-label="Kembali ke daftar event">← BACK TO EVENTS</a>

        <section class="gallery-hero" aria-labelledby="event-title">
            <img class="gallery-hero__image" src="{{ $coverImage }}" alt="{{ $eventTitle }} at {{ $eventLocation }}" fetchpriority="high">
            <span class="gallery-hero__shade" aria-hidden="true"></span>
            <div class="gallery-hero__content">
                <p class="gallery-kicker">EVENT GALLERY / {{ $eventType }}</p>
                <h1 class="gallery-title" id="event-title">{{ $eventTitle }}</h1>
                <div class="gallery-meta">
                    <span>{{ $eventLocation }}</span>
                    @if ($eventDate)<span>{{ strtoupper($eventDate) }}</span>@endif
                    <span>{{ $eventPrice }}</span>
                </div>
                @if (filled($content->body))
                    <p class="gallery-description">{!! nl2br(e($content->body)) !!}</p>
                @endif
                <div class="payment-utilities">
                    <button class="qris-button" type="button" data-open-payment aria-haspopup="dialog">
                        <svg class="icon-outline" viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7" />
                            <rect x="14" y="3" width="7" height="7" />
                            <rect x="3" y="14" width="7" height="7" />
                            <path d="M14 14h3v3h-3zM18 18h3v3h-3zM18 14h3M14 20h2" />
                        </svg>
                        PAY / QRIS
                    </button>
                    <a class="help-link" href="{{ $helpUrl }}" @if ($whatsAppNumber !== '') target="_blank" rel="noopener noreferrer" @endif>
                        <svg class="whatsapp-icon" viewBox="0 0 448 512" aria-hidden="true">
                            <path d="M380.9 97.1c-41.9-42-97.7-65.1-157-65.1-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480 117.7 449.1c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1s56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6zM325.1 300.5c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8s-14.3 18-17.6 21.8c-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.6 57.4c2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4s4.6-24.1 3.2-26.4c-1.3-2.5-5-3.9-10.5-6.6z" />
                        </svg>
                        NEED HELP?
                    </a>
                </div>
            </div>
        </section>

        @if ($content->ai_photo_search_enabled)
            <section class="ai-photo-search"
                data-ai-photo-search
                data-endpoint="{{ route('api.public.preview.ai-photo-search', $content) }}"
                aria-labelledby="ai-photo-search-title">
                <div class="ai-photo-search__header">
                    <p class="gallery-kicker">AI PHOTO SEARCH</p>
                    <h2 id="ai-photo-search-title">FIND YOUR MOTORCYCLE<span class="accent">.</span></h2>
                    <p>Upload foto motor yang jelas. AI akan menampilkan kemungkinan foto yang cocok dari event ini; hasil mungkin tidak selalu tepat.</p>
                </div>

                <form class="ai-photo-search__form" data-ai-photo-search-form enctype="multipart/form-data">
                    <label class="ai-photo-search__picker">
                        <span data-ai-photo-search-file>CHOOSE MOTORCYCLE PHOTO</span>
                        <input type="file"
                            name="photo"
                            accept="image/jpeg,image/png,image/webp"
                            data-ai-photo-search-input>
                    </label>
                    <button class="ai-photo-search__submit" type="submit" data-ai-photo-search-submit disabled>
                        FIND POSSIBLE MATCHES
                    </button>
                </form>

                <img class="ai-photo-search__preview" data-ai-photo-search-preview src="" alt="Foto motor yang dipilih">
                <p class="ai-photo-search__brand" data-ai-photo-search-brand></p>
                <p class="ai-photo-search__message" data-ai-photo-search-message role="status" aria-live="polite"></p>

                <div class="ai-photo-search__results" data-ai-photo-search-results>
                    <div class="ai-photo-search__results-heading">
                        <h3>POSSIBLE MATCHES<span class="accent">.</span></h3>
                        <span data-ai-photo-search-count></span>
                    </div>
                    <div class="ai-photo-search__grid" data-ai-photo-search-grid></div>
                </div>
            </section>
        @endif

        <section aria-labelledby="photos-heading" data-gallery-section>
            <div class="gallery-filter-group">
                <p class="gallery-filter-label"
                    title="Kategori dikenali otomatis oleh AI dan mungkin tidak selalu akurat">
                    FILTER BY CATEGORY · BY AI
                </p>
                <nav class="gallery-brands" aria-label="Filter kategori motor">
                    @foreach ($categoryFilters as $categoryFilter)
                        <a class="gallery-brand {{ $selectedCategory === $categoryFilter['slug'] ? 'is-active' : '' }}"
                            data-gallery-filter
                            data-filter-type="category"
                            data-filter-slug="{{ $categoryFilter['slug'] }}"
                            href="{{ route('preview.show', [
                                'content' => $content,
                                ...($selectedBrand !== 'all' ? ['brand' => $selectedBrand] : []),
                                ...($categoryFilter['slug'] !== 'all' ? ['category' => $categoryFilter['slug']] : []),
                            ]) }}"
                            @if ($selectedCategory === $categoryFilter['slug']) aria-current="page" @endif>
                            <span>{{ $categoryFilter['label'] }}</span>
                            <strong>{{ number_format($categoryFilter['count']) }}</strong>
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="gallery-filter-group">
                <p class="gallery-filter-label"
                    title="Merek dikenali otomatis oleh AI dan mungkin tidak selalu akurat">
                    FILTER BY BRAND · BY AI
                </p>
                <nav class="gallery-brands" aria-label="Filter merek motor">
                    @foreach ($brandFilters as $brandFilter)
                        <a class="gallery-brand {{ $selectedBrand === $brandFilter['slug'] ? 'is-active' : '' }}"
                            data-gallery-filter
                            data-filter-type="brand"
                            data-filter-slug="{{ $brandFilter['slug'] }}"
                            href="{{ route('preview.show', [
                                'content' => $content,
                                ...($selectedCategory !== 'all' ? ['category' => $selectedCategory] : []),
                                ...($brandFilter['slug'] !== 'all' ? ['brand' => $brandFilter['slug']] : []),
                            ]) }}"
                            @if ($selectedBrand === $brandFilter['slug']) aria-current="page" @endif>
                            <span>{{ $brandFilter['label'] }}</span>
                            <strong>{{ number_format($brandFilter['count']) }}</strong>
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="gallery-toolbar">
                <div>
                    <h2 id="photos-heading">EVENT PHOTOS<span class="accent">.</span></h2>
                    <p data-gallery-total>{{ number_format($photos->total()) }} PHOTOS</p>
                </div>
                <button class="qris-button qris-button--toolbar" type="button" data-open-payment aria-haspopup="dialog">
                    <svg class="icon-outline" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                        <path d="M14 14h3v3h-3zM18 18h3v3h-3zM18 14h3M14 20h2" />
                    </svg>
                    PAY / QRIS
                </button>
            </div>

            <div class="photo-grid" data-photo-grid>
                @include('landingpage.partials.preview-photo-batch', ['photos' => $photos])
            </div>

            <div class="gallery-empty" data-gallery-empty @if ($photos->isNotEmpty()) hidden @endif>
                <h2>NO MATCHING PHOTOS<span class="accent">.</span></h2>
                <p>Tidak ada foto yang cocok dengan kombinasi filter ini.</p>
            </div>

            <div class="gallery-load-more {{ $photos->hasMorePages() ? '' : 'is-complete' }}"
                data-infinite-scroll
                data-next-url="{{ $photos->hasMorePages() ? $photos->appends(['infinite' => 1])->nextPageUrl() : '' }}"
                data-loaded="{{ $photos->count() }}"
                data-total="{{ $photos->total() }}"
                @if ($photos->isEmpty()) hidden @endif
                aria-live="polite">
                <div>
                    <p class="gallery-load-more__status" data-infinite-status>
                        @if ($photos->hasMorePages())
                            SCROLL TO LOAD MORE · {{ number_format($photos->count()) }} / {{ number_format($photos->total()) }}
                        @else
                            ALL {{ number_format($photos->total()) }} PHOTOS LOADED
                        @endif
                    </p>
                    <button class="gallery-load-more__retry" type="button" data-infinite-retry>
                        TRY AGAIN
                    </button>
                </div>
            </div>
        </section>
    </main>

    <div class="photo-lightbox" data-lightbox role="dialog" aria-modal="true" aria-label="Photo preview" aria-hidden="true">
        <div class="photo-lightbox__top">
            <span data-lightbox-count></span>
            <div style="display:inline-flex; align-items:center; gap:10px;">
                <button class="qris-button qris-button--lightbox" type="button" data-open-payment aria-haspopup="dialog">
                    <svg class="icon-outline" viewBox="0 0 24 24" aria-hidden="true" style="width:14px; height:14px;">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                        <path d="M14 14h3v3h-3zM18 18h3v3h-3zM18 14h3M14 20h2" />
                    </svg>
                    PAY / QRIS
                </button>
                <button class="photo-lightbox__close" type="button" data-lightbox-close aria-label="Tutup preview">×</button>
            </div>
        </div>
        <div class="photo-lightbox__stage">
            <button class="photo-lightbox__nav photo-lightbox__nav--prev" type="button" data-lightbox-prev aria-label="Foto sebelumnya">‹</button>
            <div class="photo-lightbox__frame">
                <img class="photo-lightbox__image" data-lightbox-image src="" alt="" draggable="false">
                <div class="photo-lightbox__shield" aria-hidden="true"></div>
            </div>
            <button class="photo-lightbox__nav photo-lightbox__nav--next" type="button" data-lightbox-next aria-label="Foto berikutnya">›</button>
        </div>
        <div class="photo-lightbox__caption" data-lightbox-caption></div>
    </div>

    <div class="payment-modal" id="payment-modal" role="dialog" aria-modal="true"
        aria-labelledby="payment-modal-title" aria-hidden="true">
        <div class="payment-modal__dialog">
            <button class="payment-modal__close" type="button" data-close-payment aria-label="Close payment modal">×</button>
            <h2 id="payment-modal-title">PAY / QRIS</h2>
            <p>Scan kode QRIS di bawah ini untuk pembayaran, lalu kirim konfirmasi dan nomor/nama foto ke WhatsApp KAZEVIEW.</p>
            @if ($eventPrice && $eventPrice !== 'FREE PREVIEW')
                <div style="margin: 0 0 18px; padding: 10px 14px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 4px; font-size: 13px; font-weight: 600; color: #fff; display: flex; justify-content: space-between; align-items: center;">
                    <span style="opacity: 0.7; font-size: 11px; letter-spacing: 0.08em;">HARGA:</span>
                    <strong style="color: var(--color-accent, #ff2a2a); font-size: 14px;">{{ $eventPrice }}</strong>
                </div>
            @endif
            <img src="{{ asset('QRIS.png') }}" alt="KAZEVIEW QRIS payment code" style="border-radius: 6px;">
            <a class="payment-modal__help" href="{{ $helpUrl }}" @if ($whatsAppNumber !== '') target="_blank"
                rel="noopener noreferrer" @endif>
                <svg class="whatsapp-icon" viewBox="0 0 448 512" aria-hidden="true">
                    <path d="M380.9 97.1c-41.9-42-97.7-65.1-157-65.1-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480 117.7 449.1c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1s56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6zM325.1 300.5c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8s-14.3 18-17.6 21.8c-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.6 57.4c2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4s4.6-24.1 3.2-26.4c-1.3-2.5-5-3.9-10.5-6.6z" />
                </svg>
                CONFIRM VIA WHATSAPP
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    @if ($content->ai_photo_search_enabled)
        <script>
            (() => {
                const root = document.querySelector('[data-ai-photo-search]');
                if (!root) return;

                const form = root.querySelector('[data-ai-photo-search-form]');
                const input = root.querySelector('[data-ai-photo-search-input]');
                const fileLabel = root.querySelector('[data-ai-photo-search-file]');
                const submit = root.querySelector('[data-ai-photo-search-submit]');
                const preview = root.querySelector('[data-ai-photo-search-preview]');
                const brand = root.querySelector('[data-ai-photo-search-brand]');
                const message = root.querySelector('[data-ai-photo-search-message]');
                const results = root.querySelector('[data-ai-photo-search-results]');
                const count = root.querySelector('[data-ai-photo-search-count]');
                const grid = root.querySelector('[data-ai-photo-search-grid]');
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                const maxSourceBytes = 20 * 1024 * 1024;
                const maxUploadBytes = 7.5 * 1024 * 1024;
                const targetSizeRatio = 0.5;
                const maxDimension = 4096;
                let previewUrl = null;
                let compressedPhoto = null;
                let photoFingerprint = null;
                let searchCompleted = false;
                const searchedPhotoStorageKey = `kazeview:ai-photo-search:${root.dataset.endpoint}:searched`;

                const fingerprintPhoto = async (file) => {
                    const bytes = await file.arrayBuffer();

                    if (window.crypto?.subtle) {
                        const digest = await window.crypto.subtle.digest('SHA-256', bytes);

                        return [...new Uint8Array(digest)]
                            .map((byte) => byte.toString(16).padStart(2, '0'))
                            .join('');
                    }

                    // Fallback is only used by older/insecure browser contexts.
                    return `${file.size}:${file.lastModified}:${file.name}`;
                };

                const cachedSearch = (fingerprint) => {
                    if (!fingerprint) return null;

                    try {
                        const searched = JSON.parse(
                            localStorage.getItem(searchedPhotoStorageKey) || '[]',
                        );

                        if (!Array.isArray(searched)) return null;

                        const entry = searched.find((value) => (
                            typeof value === 'object'
                            && value !== null
                            && value.fingerprint === fingerprint
                        ));

                        // Old string-only entries are intentionally ignored
                        // because they do not contain results that can be shown.
                        return entry && Array.isArray(entry.matches) ? entry : null;
                    } catch {
                        return null;
                    }
                };

                const rememberSearch = (fingerprint, matches, brandGuess = null) => {
                    if (!fingerprint) return;

                    try {
                        const current = JSON.parse(
                            localStorage.getItem(searchedPhotoStorageKey) || '[]',
                        );
                        const searched = Array.isArray(current) ? current : [];
                        const cacheEntry = {
                            fingerprint,
                            cached_at: Date.now(),
                            brand_guess: brandGuess,
                            matches: matches.slice(0, 20).map((match) => ({
                                id: match.id ?? null,
                                name: match.name || 'MATCH',
                                url: match.url,
                                confidence: match.confidence || 'possible',
                            })),
                        };
                        const updated = [
                            cacheEntry,
                            ...searched.filter((value) => (
                                typeof value !== 'object'
                                || value === null
                                || value.fingerprint !== fingerprint
                            )),
                        ].slice(0, 20);

                        localStorage.setItem(
                            searchedPhotoStorageKey,
                            JSON.stringify(updated),
                        );
                    } catch {
                        // Privacy mode or a full storage quota must not break search.
                    }
                };

                const canvasToBlob = (canvas, quality) => new Promise((resolve, reject) => {
                    canvas.toBlob(
                        (blob) => blob
                            ? resolve(blob)
                            : reject(new Error('Foto tidak dapat dikompres.')),
                        'image/jpeg',
                        quality,
                    );
                });

                const loadBitmap = async (file) => {
                    if ('createImageBitmap' in window) {
                        return createImageBitmap(file, { imageOrientation: 'from-image' });
                    }

                    const sourceUrl = URL.createObjectURL(file);

                    try {
                        return await new Promise((resolve, reject) => {
                            const sourceImage = new Image();
                            sourceImage.onload = () => resolve(sourceImage);
                            sourceImage.onerror = () => reject(new Error('Foto tidak dapat dibaca.'));
                            sourceImage.src = sourceUrl;
                        });
                    } finally {
                        URL.revokeObjectURL(sourceUrl);
                    }
                };

                const compressPhoto = async (file) => {
                    const bitmap = await loadBitmap(file);
                    const sourceWidth = bitmap.width || bitmap.naturalWidth;
                    const sourceHeight = bitmap.height || bitmap.naturalHeight;
                    const scale = Math.min(1, maxDimension / Math.max(sourceWidth, sourceHeight));
                    const width = Math.max(1, Math.round(sourceWidth * scale));
                    const height = Math.max(1, Math.round(sourceHeight * scale));
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;

                    const context = canvas.getContext('2d', { alpha: false });
                    if (!context) {
                        bitmap.close?.();
                        throw new Error('Kompresi foto tidak didukung browser ini.');
                    }

                    context.fillStyle = '#ffffff';
                    context.fillRect(0, 0, width, height);
                    context.imageSmoothingEnabled = true;
                    context.imageSmoothingQuality = 'high';
                    context.drawImage(bitmap, 0, 0, width, height);
                    bitmap.close?.();

                    const targetBytes = Math.min(
                        maxUploadBytes,
                        Math.max(1, Math.round(file.size * targetSizeRatio)),
                    );
                    let quality = 0.95;
                    let blob = await canvasToBlob(canvas, quality);

                    while (blob.size > targetBytes && quality > 0.72) {
                        quality = Math.max(0.72, quality - 0.03);
                        blob = await canvasToBlob(canvas, quality);
                    }

                    if (blob.size > maxUploadBytes) {
                        throw new Error('Foto tidak dapat diperkecil ke ukuran aman. Pilih foto lain.');
                    }

                    const baseName = file.name.replace(/\.[^.]+$/, '') || 'motorcycle';
                    return new File([blob], `${baseName}-compressed.jpg`, {
                        type: 'image/jpeg',
                        lastModified: Date.now(),
                    });
                };

                const setMessage = (text = '', isError = false) => {
                    message.textContent = text;
                    message.classList.toggle('is-visible', Boolean(text));
                    message.classList.toggle('is-error', isError);
                };

                const setBrandGuess = (value = null) => {
                    const normalized = typeof value === 'string'
                        ? value.trim().toUpperCase()
                        : '';
                    const isKnown = normalized && normalized !== 'UNKNOWN';

                    brand.textContent = isKnown ? `BRAND: ${normalized}` : '';
                    brand.classList.toggle('is-visible', Boolean(isKnown));
                };

                const resetResults = () => {
                    grid.replaceChildren();
                    count.textContent = '';
                    results.classList.remove('is-visible');
                };

                const renderMatches = (matches) => {
                    grid.replaceChildren();

                    for (const [index, match] of matches.entries()) {
                        if (!match?.url) continue;

                        const card = document.createElement('button');
                        card.className = 'photo-card';
                        card.type = 'button';
                        card.dataset.photoSrc = match.url;
                        const rawName = match.name || 'PHOTO';
                        const displayName = rawName.replace(/\.[^/.]+$/, '');
                        card.dataset.photoName = displayName;
                        card.dataset.photoNumber = String(index + 1);
                        card.setAttribute('aria-label', `Buka ${displayName}`);

                        const image = document.createElement('img');
                        image.src = match.grid_url || match.url;
                        image.alt = displayName;
                        image.loading = 'lazy';
                        image.decoding = 'async';

                        const number = document.createElement('span');
                        number.className = 'photo-card__number';
                        number.textContent = `#${index + 1} | ${displayName}`;

                        card.append(image, number);
                        grid.append(card);
                    }

                    const renderedCount = grid.querySelectorAll('.photo-card').length;
                    count.textContent = `${renderedCount} PHOTOS`;
                    results.classList.add('is-visible');

                    return renderedCount;
                };

                const errorText = (payload) => {
                    const errors = payload && typeof payload === 'object' ? payload.errors : null;
                    const firstError = errors
                        ? Object.values(errors).flat().find((value) => typeof value === 'string')
                        : null;
                    return firstError || payload?.message || 'Pencarian foto gagal. Silakan coba kembali.';
                };

                input.addEventListener('change', async () => {
                    const file = input.files?.[0] || null;
                    compressedPhoto = null;
                    photoFingerprint = null;
                    searchCompleted = false;

                    if (previewUrl) {
                        URL.revokeObjectURL(previewUrl);
                        previewUrl = null;
                    }

                    preview.removeAttribute('src');
                    preview.classList.remove('is-visible');
                    resetResults();
                    setBrandGuess();
                    setMessage();
                    fileLabel.textContent = file?.name || 'CHOOSE MOTORCYCLE PHOTO';
                    submit.disabled = true;
                    submit.textContent = 'FIND POSSIBLE MATCHES';

                    if (!file) return;

                    if (!allowedTypes.includes(file.type)) {
                        input.value = '';
                        fileLabel.textContent = 'CHOOSE MOTORCYCLE PHOTO';
                        setMessage('Gunakan file JPEG, PNG, atau WebP.', true);
                        return;
                    }

                    if (file.size > maxSourceBytes) {
                        input.value = '';
                        fileLabel.textContent = 'CHOOSE MOTORCYCLE PHOTO';
                        setMessage('Ukuran foto sumber maksimal 20 MB.', true);
                        return;
                    }

                    setMessage('Menyiapkan dan mengompres foto di perangkat…');

                    try {
                        photoFingerprint = await fingerprintPhoto(file);
                        compressedPhoto = await compressPhoto(file);
                        previewUrl = URL.createObjectURL(compressedPhoto);
                        preview.src = previewUrl;
                        preview.classList.add('is-visible');

                        const originalSize = (file.size / 1024 / 1024).toFixed(1);
                        const compressedSize = (compressedPhoto.size / 1024 / 1024).toFixed(1);

                        const cached = cachedSearch(photoFingerprint);

                        if (cached) {
                            searchCompleted = true;
                            submit.disabled = true;
                            submit.textContent = 'PHOTO ALREADY SEARCHED';
                            const renderedCount = renderMatches(cached.matches);
                            setBrandGuess(cached.brand_guess);
                            setMessage(`${renderedCount} kemungkinan foto dari pencarian sebelumnya ditampilkan.`);
                        } else {
                            submit.disabled = false;
                            setMessage(`Foto siap dikirim · ${originalSize} MB → ${compressedSize} MB`);
                        }
                    } catch (error) {
                        compressedPhoto = null;
                        input.value = '';
                        fileLabel.textContent = 'CHOOSE MOTORCYCLE PHOTO';
                        setMessage(error?.message || 'Foto gagal dikompres. Silakan pilih foto lain.', true);
                    }
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const file = compressedPhoto;

                    if (!file || submit.disabled || searchCompleted) return;

                    submit.disabled = true;
                    submit.textContent = 'SEARCHING…';
                    resetResults();
                    setMessage('Menganalisis motor dan membandingkan foto event…');

                    const body = new FormData();
                    body.append('photo', file);

                    try {
                        const response = await fetch(root.dataset.endpoint, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                ...(csrf ? {'X-CSRF-TOKEN': csrf} : {}),
                            },
                            body,
                            signal: AbortSignal.timeout(90000),
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok || !payload?.data) {
                            throw new Error(errorText(payload));
                        }

                        const matches = Array.isArray(payload.data.matches)
                            ? payload.data.matches
                            : [];

                        const brandGuess = payload.data.query_analysis?.brand_guess;
                        const renderedCount = renderMatches(matches);
                        searchCompleted = true;
                        setBrandGuess(brandGuess);
                        rememberSearch(photoFingerprint, matches, brandGuess);
                        setMessage(
                            renderedCount
                                ? `${renderedCount} kemungkinan foto ditemukan.`
                                : 'Tidak ada kemungkinan foto yang cocok.',
                        );
                    } catch (error) {
                        const text = error?.name === 'TimeoutError'
                            ? 'Pencarian melewati batas waktu. Silakan coba kembali.'
                            : error?.message || 'Pencarian foto gagal. Silakan coba kembali.';
                        setMessage(text, true);
                    } finally {
                        submit.disabled = !compressedPhoto || searchCompleted;
                        submit.textContent = searchCompleted
                            ? 'PHOTO ALREADY SEARCHED'
                            : 'FIND POSSIBLE MATCHES';
                    }
                });

                window.addEventListener('beforeunload', () => {
                    if (previewUrl) URL.revokeObjectURL(previewUrl);
                });
            })();
        </script>
    @endif

    <script>
        (() => {
            const section = document.querySelector('[data-gallery-section]');
            const gallery = section?.querySelector('[data-photo-grid]');
            const sentinel = section?.querySelector('[data-infinite-scroll]');
            const status = sentinel?.querySelector('[data-infinite-status]');
            const retry = sentinel?.querySelector('[data-infinite-retry]');
            const total = section?.querySelector('[data-gallery-total]');
            const empty = section?.querySelector('[data-gallery-empty]');
            const modal = document.querySelector('[data-lightbox]');

            if (!section || !gallery || !sentinel || !status || !retry || !total || !empty || !modal) return;

            const image = modal.querySelector('[data-lightbox-image]');
            const caption = modal.querySelector('[data-lightbox-caption]');
            const count = modal.querySelector('[data-lightbox-count]');
            const close = modal.querySelector('[data-lightbox-close]');
            let active = 0;
            let previousFocus = null;
            let loading = false;
            let observer = null;
            let filterController = null;
            let batchController = null;

            const cards = () => [...gallery.querySelectorAll('[data-photo-src]')];

            const selectedFilters = (url) => ({
                category: url.searchParams.get('category') || 'all',
                brand: url.searchParams.get('brand') || 'all',
            });

            const syncFilterLinks = (url) => {
                const selected = selectedFilters(url);

                for (const link of section.querySelectorAll('[data-gallery-filter]')) {
                    const type = link.dataset.filterType;
                    const slug = link.dataset.filterSlug || 'all';
                    const next = new URL(url.origin + url.pathname);
                    const filters = { ...selected, [type]: slug };

                    for (const [filterType, filterSlug] of Object.entries(filters)) {
                        if (filterSlug !== 'all') {
                            next.searchParams.set(filterType, filterSlug);
                        }
                    }

                    link.href = next.toString();
                    link.classList.toggle('is-active', selected[type] === slug);

                    if (selected[type] === slug) {
                        link.setAttribute('aria-current', 'page');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                }
            };

            let currentScopeCards = cards;

            const show = (index) => {
                const currentCards = currentScopeCards();
                if (!currentCards.length) return;

                active = (index + currentCards.length) % currentCards.length;
                const card = currentCards[active];
                image.src = card.dataset.photoSrc;
                image.alt = card.dataset.photoName || '';
                caption.textContent = card.dataset.photoName || '';
                count.textContent = `#${card.dataset.photoNumber} | ${card.dataset.photoName || ''} · ${active + 1} / ${currentCards.length}`;
            };

            const setOpen = (open, index = active, scopeFn = cards) => {
                currentScopeCards = scopeFn;
                modal.classList.toggle('is-open', open);
                modal.setAttribute('aria-hidden', String(!open));
                document.body.style.overflow = open ? 'hidden' : '';

                if (open) {
                    previousFocus = document.activeElement;
                    show(index);
                    close.focus();
                } else {
                    image.src = '';
                    previousFocus?.focus();
                }
            };

            const setComplete = () => {
                sentinel.dataset.nextUrl = '';
                sentinel.classList.remove('is-loading', 'has-error');
                sentinel.classList.add('is-complete');
                status.textContent = `ALL ${Number(sentinel.dataset.total).toLocaleString()} PHOTOS LOADED`;
                observer?.disconnect();
            };

            const observeInfiniteScroll = () => {
                observer?.disconnect();

                if (!sentinel.dataset.nextUrl) return;

                observer = new IntersectionObserver(
                    (entries) => {
                        if (entries.some((entry) => entry.isIntersecting)) {
                            loadNextBatch();
                        }
                    },
                    { rootMargin: '600px 0px' },
                );
                observer.observe(sentinel);
            };

            const loadFilter = async (target, updateHistory = true) => {
                const url = new URL(target, window.location.href);
                url.searchParams.set('infinite', '1');

                filterController?.abort();
                batchController?.abort();

                const controller = new AbortController();
                filterController = controller;
                loading = false;
                observer?.disconnect();
                section.classList.add('is-filtering');
                section.setAttribute('aria-busy', 'true');
                status.textContent = 'FILTERING PHOTOS…';

                try {
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const payload = await response.json();
                    if (typeof payload.html !== 'string') {
                        throw new Error('Invalid response');
                    }

                    const batch = document.createElement('template');
                    batch.innerHTML = payload.html;
                    gallery.replaceChildren(
                        ...batch.content.querySelectorAll('[data-photo-src]'),
                    );

                    const visibleUrl = new URL(url);
                    visibleUrl.searchParams.delete('infinite');
                    syncFilterLinks(visibleUrl);

                    if (updateHistory) {
                        window.history.pushState({}, '', visibleUrl);
                    }

                    const loaded = cards().length;
                    sentinel.dataset.loaded = String(loaded);
                    sentinel.dataset.total = String(payload.total);
                    sentinel.dataset.nextUrl = payload.next_url || '';
                    sentinel.classList.remove('is-loading', 'has-error', 'is-complete');
                    sentinel.hidden = loaded === 0;
                    empty.hidden = loaded !== 0;
                    total.textContent = `${Number(payload.total).toLocaleString()} PHOTOS`;

                    if (payload.next_url) {
                        status.textContent = `SCROLL TO LOAD MORE · ${loaded.toLocaleString()} / ${Number(payload.total).toLocaleString()}`;
                        observeInfiniteScroll();
                    } else {
                        setComplete();
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        status.textContent = 'COULD NOT FILTER PHOTOS';
                        sentinel.classList.add('has-error');
                        observeInfiniteScroll();
                    }
                } finally {
                    if (filterController === controller) {
                        filterController = null;
                        section.classList.remove('is-filtering');
                        section.removeAttribute('aria-busy');
                    }
                }
            };

            const loadNextBatch = async () => {
                const nextUrl = sentinel.dataset.nextUrl;
                if (loading || !nextUrl) return;

                loading = true;
                const controller = new AbortController();
                batchController = controller;
                sentinel.classList.remove('has-error');
                sentinel.classList.add('is-loading');
                status.textContent = 'LOADING MORE PHOTOS…';

                try {
                    const response = await fetch(nextUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const payload = await response.json();
                    if (typeof payload.html !== 'string') {
                        throw new Error('Invalid response');
                    }

                    const batch = document.createElement('template');
                    batch.innerHTML = payload.html;

                    for (const incomingCard of batch.content.querySelectorAll('[data-photo-src]')) {
                        const duplicate = cards().some(
                            (card) => card.dataset.photoSrc === incomingCard.dataset.photoSrc,
                        );

                        if (!duplicate) {
                            gallery.append(incomingCard);
                        }
                    }

                    const loaded = cards().length;
                    sentinel.dataset.loaded = String(loaded);
                    sentinel.dataset.nextUrl = payload.next_url || '';
                    sentinel.classList.remove('is-loading');

                    if (payload.next_url) {
                        status.textContent = `SCROLL TO LOAD MORE · ${loaded.toLocaleString()} / ${Number(payload.total).toLocaleString()}`;
                    } else {
                        setComplete();
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        sentinel.classList.remove('is-loading');
                        sentinel.classList.add('has-error');
                        status.textContent = 'COULD NOT LOAD MORE PHOTOS';
                    }
                } finally {
                    if (batchController === controller) {
                        batchController = null;
                        loading = false;
                    }
                }
            };

            section.addEventListener('click', (event) => {
                const filter = event.target.closest('[data-gallery-filter]');
                if (!filter || !section.contains(filter)) return;

                event.preventDefault();
                loadFilter(filter.href);
            });

            window.addEventListener('popstate', () => {
                loadFilter(window.location.href, false);
            });

            document.addEventListener('click', (event) => {
                const card = event.target.closest('[data-photo-src]');
                if (!card) return;

                const aiContainer = card.closest('[data-ai-photo-search-results]') || card.closest('.ai-photo-search');
                if (aiContainer) {
                    const aiCards = () => [...aiContainer.querySelectorAll('[data-photo-src]')];
                    const index = aiCards().indexOf(card);
                    if (index !== -1) {
                        setOpen(true, index, aiCards);
                        return;
                    }
                }

                const currentCards = cards();
                const index = currentCards.indexOf(card);
                if (index !== -1) {
                    setOpen(true, index, cards);
                } else if (card.dataset.photoSrc) {
                    image.src = card.dataset.photoSrc;
                    image.alt = card.dataset.photoName || '';
                    caption.textContent = card.dataset.photoName || '';
                    count.textContent = `PHOTO ${card.dataset.photoNumber || ''}`;
                    setOpen(true, 0, () => [card]);
                }
            });

            modal.addEventListener('contextmenu', (event) => {
                if (event.target.closest('.photo-lightbox__frame')) {
                    event.preventDefault();
                }
            });
            close.addEventListener('click', () => setOpen(false));
            modal.querySelector('[data-lightbox-prev]').addEventListener('click', () => show(active - 1));
            modal.querySelector('[data-lightbox-next]').addEventListener('click', () => show(active + 1));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) setOpen(false);
            });
            modal.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setOpen(false);
                if (event.key === 'ArrowLeft') show(active - 1);
                if (event.key === 'ArrowRight') show(active + 1);
            });
            retry.addEventListener('click', loadNextBatch);

            syncFilterLinks(new URL(window.location.href));
            observeInfiniteScroll();
        })();

        (() => {
            const paymentModal = document.querySelector('#payment-modal');
            const closeBtn = paymentModal?.querySelector('[data-close-payment]');

            const setPaymentModal = (open) => {
                if (!paymentModal) return;
                paymentModal.classList.toggle('is-open', open);
                paymentModal.setAttribute('aria-hidden', String(!open));
                document.body.style.overflow = open ? 'hidden' : '';

                if (open) {
                    closeBtn?.focus();
                }
            };

            document.addEventListener('click', (event) => {
                const openBtn = event.target.closest('[data-open-payment]');
                if (openBtn) {
                    event.preventDefault();
                    setPaymentModal(true);
                    return;
                }

                if (event.target.closest('[data-close-payment]')) {
                    event.preventDefault();
                    setPaymentModal(false);
                    return;
                }

                if (event.target === paymentModal) {
                    setPaymentModal(false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && paymentModal?.classList.contains('is-open')) {
                    event.preventDefault();
                    setPaymentModal(false);
                }
            });
        })();
    </script>
@endsection