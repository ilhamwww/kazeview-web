@extends('layouts.app')

@section('title', 'KAZEVIEW — Photo + Film')
@section('meta_description', 'KAZEVIEW captures motion in every frame through automotive, portrait, and event photography and film.')

@php
    $fallbackImage = !empty($data_web->hero_image)
        ? asset('storage/' . $data_web->hero_image)
        : asset('KAZE_icon.png');

    $media = collect($galleryImages ?? [])
        ->filter(fn($item) => !empty($item->image) && (bool) ($item->is_active ?? true))
        ->map(function ($item) {
            $category = strtoupper(trim((string) ($item->category ?? 'PHOTOGRAPHY')));
            $category = in_array($category, ['PHOTOGRAPHY', 'AUTOMOTIVE', 'PORTRAITS', 'EVENTS'], true)
                ? $category
                : 'PHOTOGRAPHY';
            $mediaType = strtoupper(trim((string) ($item->media_type ?? 'PHOTOGRAPHY')));
            $mediaType = in_array($mediaType, ['PHOTOGRAPHY', 'FILM'], true) ? $mediaType : 'PHOTOGRAPHY';

            $position = trim((string) ($item->image_position ?? '50% 50%'));
            if (! preg_match('/^(left|center|right|\d{1,3}%)(\s+(top|center|bottom|\d{1,3}%))?$/', $position)) {
                $position = '50% 50%';
            }

            return [
                'id' => $item->id,
                'image' => asset('storage/' . $item->image),
                'title' => strtoupper(trim((string) ($item->title ?? 'KAZEVIEW PROJECT'))),
                'link' => !empty($item->link) ? $item->link : '#work',
                'category' => $category,
                'category_slug' => strtolower($category),
                'media_type' => $mediaType,
                'duration' => $item->duration ?? null,
                'year' => $item->project_year
                    ?? (!empty($item->created_at)
                        ? \Illuminate\Support\Carbon::parse($item->created_at)->format('Y')
                        : now()->year),
                'position' => $position,
                'is_featured' => (bool) ($item->is_featured ?? false),
            ];
        })
        ->values();

    $fallback = [
        'id' => null,
        'image' => $fallbackImage,
        'title' => 'KAZEVIEW PROJECT',
        'link' => '#work',
        'category' => 'PHOTOGRAPHY',
        'category_slug' => 'photography',
        'media_type' => 'PHOTOGRAPHY',
        'duration' => null,
        'year' => now()->year,
        'position' => '50% 50%',
        'is_featured' => false,
    ];

    if ($media->isEmpty()) {
        $media = collect([$fallback]);
    }

    $featured = $media->firstWhere('is_featured', true) ?? $media->first();
    $secondaryTiles = $media
        ->reject(fn($item) => $item['id'] === $featured['id'])
        ->take(4)
        ->values();

    while ($secondaryTiles->count() < 4) {
        $secondaryTiles->push($fallback);
    }

    $portfolioMedia = $media->reject(fn($item) => $item['id'] === $featured['id'])->values();
    $portfolioCount = $portfolioMedia->count();
@endphp

@section('content')
    <section class="home-featured-grid" id="films" aria-label="Featured KAZEVIEW work">
        <a class="media-tile featured-film" href="{{ $featured['link'] }}"
            aria-label="View {{ $featured['title'] }} project">
            <img class="media-tile__image" src="{{ $featured['image'] }}" alt="{{ $featured['title'] }} by KAZEVIEW"
                style="object-position: {{ $featured['position'] }}" fetchpriority="high">

            <span class="featured-film__play-cluster" aria-hidden="true">
                <span class="play-circle">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 4.8v14.4L19 12 7 4.8Z" />
                    </svg>
                </span>
                <span class="featured-film__meta">
                    <span class="featured-film__label">{{ $featured['media_type'] }}</span>
                    <span class="featured-film__title">{{ $featured['title'] }}</span>
                    @if ($featured['duration'])
                        <span class="featured-film__duration">{{ $featured['duration'] }}</span>
                    @endif
                </span>
            </span>

            <span class="featured-film__statement" aria-hidden="true">
                <h1>MOTION IN<br>EVERY FRAME<span class="accent">.</span></h1>
                <p>Photo + Film / Automotive · Portrait · Event</p>
            </span>

            <span class="scroll-indicator" aria-hidden="true">SCROLL TO EXPLORE</span>
        </a>

        <div class="home-featured-grid__secondary">
            @foreach ($secondaryTiles as $tile)
                <a class="media-tile secondary-tile" href="{{ $tile['link'] }}"
                    aria-label="View {{ strtolower($tile['category']) }} {{ strtolower($tile['media_type']) }}">
                    <img class="media-tile__image" src="{{ $tile['image'] }}"
                        alt="{{ $tile['title'] }} by KAZEVIEW"
                        style="object-position: {{ $tile['position'] }}" loading="eager">
                    <span class="secondary-tile__label" aria-hidden="true">
                        <span class="secondary-tile__category">{{ $tile['category'] }}</span>
                        <span class="secondary-tile__year">{{ $tile['year'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    <nav class="home-filter-bar" aria-label="Filter portfolio">
        <ul class="home-filter-list">
            @foreach (['all' => 'ALL', 'photography' => 'PHOTOGRAPHY', 'films' => 'FILMS', 'automotive' => 'AUTOMOTIVE', 'portraits' => 'PORTRAITS', 'events' => 'EVENTS'] as $value => $label)
                <li>
                    <button class="filter-button {{ $loop->first ? 'is-active' : '' }}" type="button"
                        data-home-filter="{{ $value }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                        {{ $label }}
                    </button>
                </li>
            @endforeach
        </ul>
    </nav>

    <section class="home-portfolio-grid" id="work" aria-label="KAZEVIEW portfolio">
        @foreach ($portfolioMedia as $item)
            @php
                $isFilm = $item['media_type'] === 'FILM';
                $filterTags = $isFilm
                    ? 'films ' . $item['category_slug']
                    : 'photography ' . $item['category_slug'];
            @endphp
            <a class="home-portfolio-card {{ $isFilm ? 'home-portfolio-card--film' : '' }}"
                href="{{ $item['link'] }}" data-home-category="{{ $filterTags }}"
                aria-label="{{ $item['media_type'] }}, {{ $item['title'] }}, {{ $item['category'] }}">
                <img src="{{ $item['image'] }}"
                    alt="{{ $item['title'] }} — {{ strtolower($item['category']) }} by KAZEVIEW"
                    style="object-position: {{ $item['position'] }}"
                    loading="{{ $loop->index < 4 ? 'eager' : 'lazy' }}">

                @if ($isFilm)
                    <span class="portfolio-play" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M7 4.8v14.4L19 12 7 4.8Z" />
                        </svg>
                    </span>
                    @if ($item['duration'])
                        <span class="portfolio-duration" aria-hidden="true">{{ $item['duration'] }}</span>
                    @endif
                @endif

                <span class="portfolio-card__meta" aria-hidden="true">
                    <span class="portfolio-card__category">{{ $item['category'] }}</span>
                    <span class="portfolio-card__title">{{ $item['title'] }}</span>
                </span>
            </a>
        @endforeach
    </section>

    <section id="about" class="sr-only" aria-label="About KAZEVIEW">
        <h2>About KAZEVIEW</h2>
        <p>Automotive, portrait, and event photography and films.</p>
    </section>
@endsection

@section('scripts')
    <script>
        (() => {
            const buttons = [...document.querySelectorAll('[data-home-filter]')];
            const cards = [...document.querySelectorAll('[data-home-category]')];
            let filterTimer;

            const applyFilter = (filter) => {
                window.clearTimeout(filterTimer);
                cards.forEach((card) => card.classList.add('is-filtering'));

                filterTimer = window.setTimeout(() => {
                    cards.forEach((card) => {
                        const categories = card.dataset.homeCategory.split(' ');
                        const visible = filter === 'all' || categories.includes(filter);
                        card.classList.toggle('is-hidden', !visible);
                        card.classList.remove('is-filtering');
                    });
                }, 160);
            };

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    buttons.forEach((item) => {
                        const active = item === button;
                        item.classList.toggle('is-active', active);
                        item.setAttribute('aria-pressed', String(active));
                    });
                    applyFilter(button.dataset.homeFilter);
                });
            });
        })();
    </script>
@endsection