@extends('layouts.app')

@section('title', 'KAZEVIEW — Photo + Film')
@section('meta_description', 'KAZEVIEW captures motion in every frame through automotive, portrait, and event photography and film.')

@php
    $collectionMedia = collect($galleryImages ?? [])
        ->filter(fn($item) => !empty($item->image))
        ->map(
            fn($item) => [
                'image' => asset('storage/' . $item->image),
                'title' => $item->title ?? 'KAZEVIEW Project',
                'link' => '#work',
            ],
        );

    $fallbackImage = !empty($data_web->hero_image)
        ? asset('storage/' . $data_web->hero_image)
        : asset('KAZE_icon.png');

    $media = $collectionMedia->values();

    if ($media->isEmpty()) {
        $media = collect([
            [
                'image' => $fallbackImage,
                'title' => 'KAZEVIEW Project',
                'link' => '#work',
            ],
        ]);
    }

    $getMedia = fn(int $index) => $media[$index % $media->count()];
    $featured = $getMedia(0);

    $secondaryTiles = [
        ['media' => $getMedia(1), 'category' => 'AUTOMOTIVE', 'year' => '2024', 'position' => '50% 48%'],
        ['media' => $getMedia(2), 'category' => 'PORTRAITS', 'year' => '2024', 'position' => '50% 30%'],
        ['media' => $getMedia(3), 'category' => 'EVENTS', 'year' => '2024', 'position' => '50% 55%'],
        ['media' => $getMedia(4), 'category' => 'AUTOMOTIVE', 'year' => '2024', 'position' => '56% 52%'],
    ];

    $portfolioCategories = ['films', 'portraits', 'automotive', 'events', 'automotive', 'portraits', 'events', 'photography'];
    $portfolioLabels = ['FILM', 'PORTRAITS', 'AUTOMOTIVE', 'EVENTS', 'AUTOMOTIVE', 'PORTRAITS', 'EVENTS', 'PHOTOGRAPHY'];
    $portfolioCount = max(8, $media->count());
@endphp

@section('content')
    <section class="home-featured-grid" id="films" aria-label="Featured KAZEVIEW work">
        <a class="media-tile featured-film" href="{{ $featured['link'] }}"
            aria-label="Play Night Run — Surabaya, duration 1 minute 24 seconds">
            <img class="media-tile__image" src="{{ $featured['image'] }}" alt="Night automotive film by KAZEVIEW"
                fetchpriority="high">

            <span class="featured-film__play-cluster" aria-hidden="true">
                <span class="play-circle">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 4.8v14.4L19 12 7 4.8Z" />
                    </svg>
                </span>
                <span class="featured-film__meta">
                    <span class="featured-film__label">FEATURED FILM</span>
                    <span class="featured-film__title">NIGHT RUN — SURABAYA</span>
                    <span class="featured-film__duration">01:24</span>
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
                <a class="media-tile secondary-tile" href="{{ $tile['media']['link'] }}"
                    aria-label="View {{ strtolower($tile['category']) }} photography from {{ $tile['year'] }}">
                    <img class="media-tile__image" src="{{ $tile['media']['image'] }}"
                        alt="{{ ucfirst(strtolower($tile['category'])) }} photography by KAZEVIEW"
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
        @for ($index = 0; $index < $portfolioCount; $index++)
            @php
                $item = $getMedia($index + 5);
                $category = $portfolioCategories[$index % count($portfolioCategories)];
                $categoryLabel = $portfolioLabels[$index % count($portfolioLabels)];
                $isFilm = $index === 0 || $category === 'films';
                $filterTags = $isFilm ? 'films' : 'photography ' . $category;
                $title = $index === 0 ? 'TRACKSIDE — REDLINE' : strtoupper($item['title']);
            @endphp
            <a class="home-portfolio-card {{ $isFilm ? 'home-portfolio-card--film' : '' }}" href="{{ $item['link'] }}"
                data-home-category="{{ $filterTags }}"
                aria-label="{{ $isFilm ? 'Film' : 'Photography' }}, {{ $title }}, {{ $categoryLabel }}">
                <img src="{{ $item['image'] }}" alt="{{ $title }} — {{ strtolower($categoryLabel) }} by KAZEVIEW"
                    loading="{{ $index < 4 ? 'eager' : 'lazy' }}">

                @if ($isFilm)
                    <span class="portfolio-play" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M7 4.8v14.4L19 12 7 4.8Z" />
                        </svg>
                    </span>
                    <span class="portfolio-duration" aria-hidden="true">{{ $index === 0 ? '01:37' : '01:24' }}</span>
                @endif

                <span class="portfolio-card__meta" aria-hidden="true">
                    <span class="portfolio-card__category">{{ $categoryLabel }}</span>
                    <span class="portfolio-card__title">{{ $title }}</span>
                </span>
            </a>
        @endfor
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