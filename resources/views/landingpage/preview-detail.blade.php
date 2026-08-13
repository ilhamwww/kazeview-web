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
    $topLevelFolders = $folders->where('depth', 1);
    $photoGroups = $photos->getCollection()->groupBy(
        fn ($photo) => $photo->folder?->relative_path ?: 'ROOT / UNCATEGORIZED',
    );
@endphp

@section('preloads')
    <link rel="preload" href="{{ $coverImage }}" as="image" fetchpriority="high">
@endsection

@section('styles')
    <style>
        .gallery-detail { padding: clamp(2rem, 5vw, 5rem) var(--page-gutter, 5vw) 6rem; }
        .gallery-back { display:inline-flex; align-items:center; gap:.6rem; color:inherit; text-decoration:none; font:700 .78rem/1 Inter,sans-serif; letter-spacing:.12em; margin-bottom:2rem; }
        .gallery-back:hover { color:#ff4d00; }
        .gallery-hero { position:relative; min-height:min(68vh,680px); display:flex; align-items:flex-end; overflow:hidden; background:#111; }
        .gallery-hero__image,.gallery-hero__shade { position:absolute; inset:0; width:100%; height:100%; }
        .gallery-hero__image { object-fit:cover; }
        .gallery-hero__shade { background:linear-gradient(180deg,rgba(0,0,0,.08),rgba(0,0,0,.82)); }
        .gallery-hero__content { position:relative; z-index:1; color:#fff; padding:clamp(1.5rem,5vw,4.5rem); max-width:980px; }
        .gallery-kicker { color:#ff5a16; font:800 .75rem/1 Inter,sans-serif; letter-spacing:.16em; }
        .gallery-title { margin:.75rem 0 1rem; font:800 clamp(2.3rem,7vw,6.5rem)/.9 "Inter Tight",sans-serif; letter-spacing:-.055em; }
        .gallery-meta { display:flex; flex-wrap:wrap; gap:.65rem 1.5rem; font:600 .8rem/1.4 Inter,sans-serif; letter-spacing:.08em; }
        .gallery-description { max-width:720px; margin:1.3rem 0 0; color:rgba(255,255,255,.76); line-height:1.7; }
        .ai-photo-search { margin:clamp(2rem,5vw,4.5rem) 0 0; padding:clamp(1.4rem,4vw,3.5rem); border:1px solid rgba(127,127,127,.32); background:radial-gradient(circle at 100% 0,rgba(255,90,22,.12),transparent 42%),#0c0c0d; }
        .ai-photo-search__header { max-width:760px; }
        .ai-photo-search__header h2 { margin:.7rem 0 1rem; font:800 clamp(2rem,5vw,4.5rem)/.92 "Inter Tight",sans-serif; letter-spacing:-.05em; }
        .ai-photo-search__header > p:last-child { margin:0; opacity:.68; line-height:1.65; }
        .ai-photo-search__form { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:center; margin-top:1.75rem; }
        .ai-photo-search__picker { min-width:0; min-height:54px; display:flex; align-items:center; padding:0 1rem; overflow:hidden; border:1px solid rgba(127,127,127,.42); cursor:pointer; font:700 .72rem/1 Inter,sans-serif; letter-spacing:.09em; }
        .ai-photo-search__picker:hover { border-color:#ff5a16; }
        .ai-photo-search__picker span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ai-photo-search__picker input { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }
        .ai-photo-search__submit { min-height:54px; padding:0 1.5rem; border:1px solid #ff5a16; color:#050506; background:#ff5a16; cursor:pointer; font:800 .72rem/1 Inter,sans-serif; letter-spacing:.09em; }
        .ai-photo-search__submit:hover:not(:disabled) { color:#fff; background:transparent; }
        .ai-photo-search__submit:disabled { opacity:.42; cursor:not-allowed; }
        .ai-photo-search__preview { display:none; width:min(100%,320px); max-height:280px; margin-top:1rem; object-fit:contain; background:#050506; }
        .ai-photo-search__preview.is-visible { display:block; }
        .ai-photo-search__privacy { margin:.8rem 0 0; opacity:.5; font-size:.75rem; line-height:1.5; }
        .ai-photo-search__message { display:none; margin:1rem 0 0; padding:.85rem 1rem; border-left:3px solid #ff5a16; background:rgba(255,90,22,.08); line-height:1.5; }
        .ai-photo-search__message.is-visible { display:block; }
        .ai-photo-search__message.is-error { color:#ff9f78; border-left-color:#ff6b6b; background:rgba(255,107,107,.08); }
        .ai-photo-search__results { display:none; margin-top:2.5rem; padding-top:2rem; border-top:1px solid rgba(127,127,127,.3); }
        .ai-photo-search__results.is-visible { display:block; }
        .ai-photo-search__results-heading { display:flex; justify-content:space-between; align-items:end; gap:1rem; margin-bottom:1rem; }
        .ai-photo-search__results-heading h3 { margin:0; font:800 clamp(1.5rem,4vw,2.8rem)/1 "Inter Tight",sans-serif; letter-spacing:-.04em; }
        .ai-photo-search__results-heading span { opacity:.55; font:700 .68rem/1 Inter,sans-serif; letter-spacing:.1em; }
        .ai-photo-search__grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:clamp(.5rem,1.2vw,1rem); }
        .ai-photo-search__match { position:relative; display:block; overflow:hidden; aspect-ratio:4/3; color:#fff; background:#151515; }
        .ai-photo-search__match img { width:100%; height:100%; object-fit:cover; transition:transform .45s ease; }
        .ai-photo-search__match:hover img { transform:scale(1.035); }
        .ai-photo-search__match-meta { position:absolute; right:0; bottom:0; left:0; display:flex; justify-content:space-between; gap:.5rem; padding:.7rem; background:linear-gradient(transparent,rgba(0,0,0,.9)); font:700 .62rem/1.3 Inter,sans-serif; letter-spacing:.06em; text-transform:uppercase; }
        .gallery-folders { display:flex; flex-wrap:wrap; gap:.5rem; margin:clamp(3rem,7vw,6rem) 0 0; }
        .gallery-folder { display:inline-flex; align-items:center; gap:.5rem; min-height:42px; padding:.7rem 1rem; border:1px solid rgba(127,127,127,.32); color:inherit; text-decoration:none; font:700 .72rem/1 Inter,sans-serif; letter-spacing:.09em; text-transform:uppercase; }
        .gallery-folder:hover,.gallery-folder:focus-visible,.gallery-folder.is-active { border-color:#ff5a16; background:#ff5a16; color:#050506; outline:none; }
        .gallery-folder small { opacity:.65; font:inherit; }
        .gallery-toolbar { display:flex; justify-content:space-between; align-items:end; gap:1rem; margin:2rem 0 1.5rem; border-bottom:1px solid rgba(127,127,127,.32); padding-bottom:1rem; }
        .gallery-toolbar h2 { margin:0; font:800 clamp(1.7rem,4vw,3.2rem)/1 "Inter Tight",sans-serif; letter-spacing:-.04em; }
        .gallery-toolbar p { margin:0; font:700 .75rem/1 Inter,sans-serif; letter-spacing:.12em; opacity:.6; }
        .photo-group + .photo-group { margin-top:3.5rem; }
        .photo-group__heading { display:flex; justify-content:space-between; align-items:end; gap:1rem; margin:0 0 1rem; }
        .photo-group__heading h3 { margin:0; font:800 clamp(1.25rem,3vw,2rem)/1 "Inter Tight",sans-serif; letter-spacing:-.03em; text-transform:uppercase; }
        .photo-group__heading span { opacity:.55; font:700 .68rem/1 Inter,sans-serif; letter-spacing:.1em; }
        .photo-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:clamp(.5rem,1.2vw,1rem); }
        .photo-card { position:relative; border:0; padding:0; overflow:hidden; background:#151515; cursor:zoom-in; aspect-ratio:4/3; }
        .photo-card img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .45s ease,opacity .25s; }
        .photo-card:hover img,.photo-card:focus-visible img { transform:scale(1.035); }
        .photo-card__number { position:absolute; right:.65rem; bottom:.65rem; padding:.4rem .55rem; color:#fff; background:rgba(0,0,0,.72); font:700 .65rem/1 Inter,sans-serif; letter-spacing:.08em; }
        .gallery-empty { padding:5rem 1.5rem; text-align:center; border:1px solid rgba(127,127,127,.28); }
        .gallery-empty h2 { font:800 clamp(1.8rem,5vw,3.5rem)/1 "Inter Tight",sans-serif; margin:0 0 1rem; }
        .gallery-empty p { opacity:.65; margin:0; }
        .gallery-pagination { display:flex; align-items:center; justify-content:space-between; gap:2rem; margin-top:3rem; padding-top:1.5rem; border-top:1px solid rgba(127,127,127,.28); }
        .gallery-pagination__summary { margin:0; color:inherit; opacity:.65; font:600 .72rem/1.4 Inter,sans-serif; letter-spacing:.08em; text-transform:uppercase; }
        .gallery-pagination__nav { display:flex; align-items:center; gap:.35rem; }
        .gallery-pagination__link { min-width:42px; height:42px; display:inline-flex; align-items:center; justify-content:center; border:1px solid rgba(127,127,127,.32); color:inherit; background:transparent; text-decoration:none; font:700 .75rem/1 Inter,sans-serif; transition:background-color .2s,color .2s,border-color .2s; }
        .gallery-pagination__link:hover,.gallery-pagination__link:focus-visible { border-color:#ff5a16; color:#ff5a16; outline:none; }
        .gallery-pagination__link.is-active { border-color:#ff5a16; background:#ff5a16; color:#050506; }
        .gallery-pagination__link.is-disabled { opacity:.28; cursor:not-allowed; pointer-events:none; }
        .gallery-pagination__direction { min-width:96px; padding:0 .9rem; gap:.45rem; letter-spacing:.08em; }
        .gallery-pagination__ellipsis { min-width:30px; text-align:center; opacity:.45; font-weight:700; }
        .photo-lightbox { position:fixed; inset:0; z-index:1000; display:none; background:rgba(0,0,0,.94); color:#fff; }
        .photo-lightbox.is-open { display:grid; grid-template-rows:auto 1fr auto; }
        .photo-lightbox__top { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.25rem; }
        .photo-lightbox__close,.photo-lightbox__nav { border:1px solid rgba(255,255,255,.35); background:rgba(0,0,0,.35); color:#fff; cursor:pointer; }
        .photo-lightbox__close { width:44px;height:44px;font-size:1.5rem; }
        .photo-lightbox__stage { position:relative; min-height:0; display:flex; align-items:center; justify-content:center; padding:0 4rem; }
        .photo-lightbox__image { max-width:100%; max-height:calc(100vh - 150px); object-fit:contain; }
        .photo-lightbox__nav { position:absolute; top:50%; transform:translateY(-50%); width:48px;height:64px;font-size:1.5rem; }
        .photo-lightbox__nav--prev { left:.75rem; } .photo-lightbox__nav--next { right:.75rem; }
        .photo-lightbox__caption { padding:1rem 1.25rem; text-align:center; font:.75rem/1.4 Inter,sans-serif; letter-spacing:.08em; }
        @media(max-width:900px){.photo-grid,.ai-photo-search__grid{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:640px){.gallery-detail{padding-left:1rem;padding-right:1rem}.gallery-hero{min-height:58vh}.photo-grid,.ai-photo-search__grid{grid-template-columns:repeat(2,1fr)}.ai-photo-search__form{grid-template-columns:1fr}.ai-photo-search__submit{width:100%}.ai-photo-search__results-heading{align-items:start;flex-direction:column}.gallery-toolbar{align-items:start;flex-direction:column}.photo-lightbox__stage{padding:0 2.75rem}.gallery-pagination{align-items:stretch;flex-direction:column;gap:1rem}.gallery-pagination__summary{text-align:center}.gallery-pagination__nav{justify-content:center;flex-wrap:wrap}.gallery-pagination__direction{min-width:42px}.gallery-pagination__direction span{display:none}}
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
                <p class="ai-photo-search__privacy">File diproses sementara dan tidak ditambahkan ke galeri event.</p>
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

        <section aria-labelledby="photos-heading">
            @if ($topLevelFolders->isNotEmpty())
                <nav class="gallery-folders" aria-label="Kategori foto">
                    <a class="gallery-folder {{ $selectedFolder === null ? 'is-active' : '' }}"
                        href="{{ route('preview.show', $content) }}">
                        ALL PHOTOS <small>{{ number_format($content->downloads()->sum('total_files')) }}</small>
                    </a>
                    @foreach ($topLevelFolders as $folder)
                        <a class="gallery-folder {{ $selectedFolder?->id === $folder->id ? 'is-active' : '' }}"
                            href="{{ route('preview.show', ['content' => $content, 'folder' => $folder->id]) }}">
                            {{ $folder->name }} <small>{{ number_format($folder->total_image_count) }}</small>
                        </a>
                    @endforeach
                </nav>
            @endif

            <div class="gallery-toolbar">
                <h2 id="photos-heading">EVENT PHOTOS<span class="accent">.</span></h2>
                <p>{{ number_format($photos->total()) }} PHOTOS · PAGE {{ $photos->currentPage() }} / {{ max(1, $photos->lastPage()) }}</p>
            </div>

            @if ($photos->isNotEmpty())
                @php $photoOffset = 0; @endphp
                <div data-photo-grid>
                    @foreach ($photoGroups as $folderPath => $groupPhotos)
                        <section class="photo-group" aria-labelledby="folder-{{ $loop->index }}">
                            <div class="photo-group__heading">
                                <h3 id="folder-{{ $loop->index }}">{{ $folderPath }}</h3>
                                <span>{{ number_format($groupPhotos->count()) }} PHOTOS ON THIS PAGE</span>
                            </div>
                            <div class="photo-grid">
                                @foreach ($groupPhotos as $photo)
                                    @php $number = $photos->firstItem() + $photoOffset++; @endphp
                                    <button class="photo-card" type="button"
                                        data-photo-src="{{ $photo->public_url }}"
                                        data-photo-name="{{ $photo->file_name }}"
                                        data-photo-number="{{ $number }}"
                                        aria-label="Buka foto {{ $number }}: {{ $photo->file_name }}">
                                        <img src="{{ route('photos.thumbnail', ['photo' => $photo->getKey()]) }}"
                                            alt="{{ $photo->file_name }}"
                                            loading="lazy"
                                            decoding="async"
                                            fetchpriority="low"
                                            onerror="this.onerror=null;this.src=@js($photo->public_url)">
                                        <span class="photo-card__number">#{{ str_pad((string) $number, 3, '0', STR_PAD_LEFT) }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
                @if ($photos->hasPages())
                    <nav class="gallery-pagination" aria-label="Navigasi halaman galeri">
                        <p class="gallery-pagination__summary">
                            Menampilkan {{ number_format($photos->firstItem()) }}–{{ number_format($photos->lastItem()) }}
                            dari {{ number_format($photos->total()) }} foto
                        </p>

                        <div class="gallery-pagination__nav">
                            @if ($photos->onFirstPage())
                                <span class="gallery-pagination__link gallery-pagination__direction is-disabled" aria-disabled="true">
                                    <b aria-hidden="true">←</b><span>PREVIOUS</span>
                                </span>
                            @else
                                <a class="gallery-pagination__link gallery-pagination__direction"
                                    href="{{ $photos->previousPageUrl() }}" rel="prev">
                                    <b aria-hidden="true">←</b><span>PREVIOUS</span>
                                </a>
                            @endif

                            @php
                                $currentPage = $photos->currentPage();
                                $lastPage = $photos->lastPage();
                                $visiblePages = collect([
                                    1,
                                    $currentPage - 1,
                                    $currentPage,
                                    $currentPage + 1,
                                    $lastPage,
                                ])
                                    ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
                                    ->unique()
                                    ->sort()
                                    ->values();
                                $previousVisiblePage = null;
                            @endphp

                            @foreach ($visiblePages as $page)
                                @if ($previousVisiblePage !== null && $page - $previousVisiblePage > 1)
                                    <span class="gallery-pagination__ellipsis" aria-hidden="true">…</span>
                                @endif

                                <a class="gallery-pagination__link {{ $page === $currentPage ? 'is-active' : '' }}"
                                    href="{{ $photos->url($page) }}"
                                    aria-label="Buka halaman {{ $page }}"
                                    @if ($page === $currentPage) aria-current="page" @endif>
                                    {{ $page }}
                                </a>

                                @php $previousVisiblePage = $page; @endphp
                            @endforeach

                            @if ($photos->hasMorePages())
                                <a class="gallery-pagination__link gallery-pagination__direction"
                                    href="{{ $photos->nextPageUrl() }}" rel="next">
                                    <span>NEXT</span><b aria-hidden="true">→</b>
                                </a>
                            @else
                                <span class="gallery-pagination__link gallery-pagination__direction is-disabled" aria-disabled="true">
                                    <span>NEXT</span><b aria-hidden="true">→</b>
                                </span>
                            @endif
                        </div>
                    </nav>
                @endif
            @else
                <div class="gallery-empty">
                    <h2>PHOTOS ARE BEING PREPARED<span class="accent">.</span></h2>
                    <p>Gallery untuk event ini belum tersedia atau sedang diperbarui.</p>
                </div>
            @endif
        </section>
    </main>

    <div class="photo-lightbox" data-lightbox role="dialog" aria-modal="true" aria-label="Photo preview" aria-hidden="true">
        <div class="photo-lightbox__top">
            <span data-lightbox-count></span>
            <button class="photo-lightbox__close" type="button" data-lightbox-close aria-label="Tutup preview">×</button>
        </div>
        <div class="photo-lightbox__stage">
            <button class="photo-lightbox__nav photo-lightbox__nav--prev" type="button" data-lightbox-prev aria-label="Foto sebelumnya">‹</button>
            <img class="photo-lightbox__image" data-lightbox-image src="" alt="">
            <button class="photo-lightbox__nav photo-lightbox__nav--next" type="button" data-lightbox-next aria-label="Foto berikutnya">›</button>
        </div>
        <div class="photo-lightbox__caption" data-lightbox-caption></div>
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
                const message = root.querySelector('[data-ai-photo-search-message]');
                const results = root.querySelector('[data-ai-photo-search-results]');
                const count = root.querySelector('[data-ai-photo-search-count]');
                const grid = root.querySelector('[data-ai-photo-search-grid]');
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                const maxBytes = 8 * 1024 * 1024;
                let previewUrl = null;

                const setMessage = (text = '', isError = false) => {
                    message.textContent = text;
                    message.classList.toggle('is-visible', Boolean(text));
                    message.classList.toggle('is-error', isError);
                };

                const resetResults = () => {
                    grid.replaceChildren();
                    count.textContent = '';
                    results.classList.remove('is-visible');
                };

                const errorText = (payload) => {
                    const errors = payload && typeof payload === 'object' ? payload.errors : null;
                    const firstError = errors
                        ? Object.values(errors).flat().find((value) => typeof value === 'string')
                        : null;
                    return firstError || payload?.message || 'Pencarian foto gagal. Silakan coba kembali.';
                };

                input.addEventListener('change', () => {
                    const file = input.files?.[0] || null;

                    if (previewUrl) {
                        URL.revokeObjectURL(previewUrl);
                        previewUrl = null;
                    }

                    preview.removeAttribute('src');
                    preview.classList.remove('is-visible');
                    resetResults();
                    setMessage();
                    fileLabel.textContent = file?.name || 'CHOOSE MOTORCYCLE PHOTO';
                    submit.disabled = !file;

                    if (!file) return;

                    if (!allowedTypes.includes(file.type)) {
                        input.value = '';
                        fileLabel.textContent = 'CHOOSE MOTORCYCLE PHOTO';
                        submit.disabled = true;
                        setMessage('Gunakan file JPEG, PNG, atau WebP.', true);
                        return;
                    }

                    if (file.size > maxBytes) {
                        input.value = '';
                        fileLabel.textContent = 'CHOOSE MOTORCYCLE PHOTO';
                        submit.disabled = true;
                        setMessage('Ukuran foto maksimal 8 MB.', true);
                        return;
                    }

                    previewUrl = URL.createObjectURL(file);
                    preview.src = previewUrl;
                    preview.classList.add('is-visible');
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const file = input.files?.[0];

                    if (!file || submit.disabled) return;

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

                        for (const match of matches) {
                            const link = document.createElement('a');
                            link.className = 'ai-photo-search__match';
                            link.href = match.url;
                            link.target = '_blank';
                            link.rel = 'noopener';
                            link.setAttribute('aria-label', `Buka ${match.name || 'foto hasil pencarian'}`);

                            const image = document.createElement('img');
                            image.src = match.url;
                            image.alt = match.name || 'Possible motorcycle match';
                            image.loading = 'lazy';
                            image.decoding = 'async';

                            const meta = document.createElement('span');
                            meta.className = 'ai-photo-search__match-meta';

                            const name = document.createElement('span');
                            name.textContent = match.name || 'MATCH';

                            const confidence = document.createElement('span');
                            confidence.textContent = String(match.confidence || 'possible');

                            meta.append(name, confidence);
                            link.append(image, meta);
                            grid.append(link);
                        }

                        count.textContent = `${matches.length} PHOTOS`;
                        results.classList.add('is-visible');
                        setMessage(
                            matches.length
                                ? `${matches.length} kemungkinan foto ditemukan.`
                                : 'Tidak ada kemungkinan foto yang cocok.',
                        );
                    } catch (error) {
                        const text = error?.name === 'TimeoutError'
                            ? 'Pencarian melewati batas waktu. Silakan coba kembali.'
                            : error?.message || 'Pencarian foto gagal. Silakan coba kembali.';
                        setMessage(text, true);
                    } finally {
                        submit.disabled = !input.files?.[0];
                        submit.textContent = 'FIND POSSIBLE MATCHES';
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
            const cards = [...document.querySelectorAll('[data-photo-src]')];
            const modal = document.querySelector('[data-lightbox]');
            if (!cards.length || !modal) return;

            const image = modal.querySelector('[data-lightbox-image]');
            const caption = modal.querySelector('[data-lightbox-caption]');
            const count = modal.querySelector('[data-lightbox-count]');
            const close = modal.querySelector('[data-lightbox-close]');
            let active = 0;
            let previousFocus = null;

            const show = (index) => {
                active = (index + cards.length) % cards.length;
                const card = cards[active];
                image.src = card.dataset.photoSrc;
                image.alt = card.dataset.photoName;
                caption.textContent = card.dataset.photoName;
                count.textContent = `PHOTO ${card.dataset.photoNumber}`;
            };

            const setOpen = (open, index = active) => {
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

            cards.forEach((card, index) => card.addEventListener('click', () => setOpen(true, index)));
            close.addEventListener('click', () => setOpen(false));
            modal.querySelector('[data-lightbox-prev]').addEventListener('click', () => show(active - 1));
            modal.querySelector('[data-lightbox-next]').addEventListener('click', () => show(active + 1));
            modal.addEventListener('click', (event) => { if (event.target === modal) setOpen(false); });
            modal.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setOpen(false);
                if (event.key === 'ArrowLeft') show(active - 1);
                if (event.key === 'ArrowRight') show(active + 1);
            });
        })();
    </script>
@endsection