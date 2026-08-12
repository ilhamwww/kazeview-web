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
        .gallery-toolbar { display:flex; justify-content:space-between; align-items:end; gap:1rem; margin:clamp(3rem,7vw,6rem) 0 1.5rem; border-bottom:1px solid rgba(127,127,127,.32); padding-bottom:1rem; }
        .gallery-toolbar h2 { margin:0; font:800 clamp(1.7rem,4vw,3.2rem)/1 "Inter Tight",sans-serif; letter-spacing:-.04em; }
        .gallery-toolbar p { margin:0; font:700 .75rem/1 Inter,sans-serif; letter-spacing:.12em; opacity:.6; }
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
        @media(max-width:900px){.photo-grid{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:640px){.gallery-detail{padding-left:1rem;padding-right:1rem}.gallery-hero{min-height:58vh}.photo-grid{grid-template-columns:repeat(2,1fr)}.gallery-toolbar{align-items:start;flex-direction:column}.photo-lightbox__stage{padding:0 2.75rem}.gallery-pagination{align-items:stretch;flex-direction:column;gap:1rem}.gallery-pagination__summary{text-align:center}.gallery-pagination__nav{justify-content:center;flex-wrap:wrap}.gallery-pagination__direction{min-width:42px}.gallery-pagination__direction span{display:none}}
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

        <section aria-labelledby="photos-heading">
            <div class="gallery-toolbar">
                <h2 id="photos-heading">EVENT PHOTOS<span class="accent">.</span></h2>
                <p>{{ number_format($photos->total()) }} PHOTOS · PAGE {{ $photos->currentPage() }} / {{ max(1, $photos->lastPage()) }}</p>
            </div>

            @if ($photos->isNotEmpty())
                <div class="photo-grid" data-photo-grid>
                    @foreach ($photos as $photo)
                        @php $number = $photos->firstItem() + $loop->index; @endphp
                        <button class="photo-card" type="button"
                            data-photo-src="{{ $photo->public_url }}"
                            data-photo-name="{{ $photo->file_name }}"
                            data-photo-number="{{ $number }}"
                            aria-label="Buka foto {{ $number }}: {{ $photo->file_name }}">
                            <img src="{{ $photo->public_url }}" alt="{{ $photo->file_name }}" loading="lazy" decoding="async">
                            <span class="photo-card__number">#{{ str_pad((string) $number, 3, '0', STR_PAD_LEFT) }}</span>
                        </button>
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

                            @foreach ($photos->getUrlRange(max(1, $photos->currentPage() - 2), min($photos->lastPage(), $photos->currentPage() + 2)) as $page => $url)
                                <a class="gallery-pagination__link {{ $page === $photos->currentPage() ? 'is-active' : '' }}"
                                    href="{{ $url }}"
                                    @if ($page === $photos->currentPage()) aria-current="page" @endif>
                                    {{ $page }}
                                </a>
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