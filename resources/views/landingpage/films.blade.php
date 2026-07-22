@extends('layouts.app')

@section('title', 'Films — KAZEVIEW')
@section('meta_description', 'Explore KAZEVIEW films — cinematic automotive, portrait, and event stories captured in motion.')

@php
    $fallbackImage = !empty($data_web->hero_image)
        ? asset('storage/' . $data_web->hero_image)
        : asset('KAZE_icon.png');

    $filmItems = collect($films ?? [])
        ->map(function ($film) use ($fallbackImage) {
            $category = strtoupper(trim((string) ($film->category ?? 'FILM')));
            $categorySlug = \Illuminate\Support\Str::slug($category) ?: 'film';

            $position = trim((string) ($film->image_position ?? '50% 50%'));
            if (! preg_match('/^(left|center|right|\d{1,3}%)(\s+(top|center|bottom|\d{1,3}%))?$/', $position)) {
                $position = '50% 50%';
            }

            return [
                'id' => $film->id,
                'title' => strtoupper(trim((string) ($film->title ?? 'UNTITLED FILM'))),
                'category' => $category,
                'category_slug' => $categorySlug,
                'year' => $film->project_year
                    ?? (!empty($film->created_at)
                        ? \Illuminate\Support\Carbon::parse($film->created_at)->format('Y')
                        : now()->year),
                'duration' => trim((string) ($film->duration ?? '')),
                'image' => !empty($film->image) ? asset('storage/' . $film->image) : $fallbackImage,
                'video' => !empty($film->video) ? asset('storage/' . $film->video) : null,
                'link' => !empty($film->link) ? $film->link : null,
                'position' => $position,
                'is_featured' => (bool) ($film->is_featured ?? false),
            ];
        })
        ->values();

    $featured = $filmItems->first();
    $filmCategories = $filmItems
        ->map(fn($film) => [
            'label' => $film['category'],
            'slug' => $film['category_slug'],
        ])
        ->unique('slug')
        ->values();
@endphp

@if ($featured)
    @section('preloads')
        <link rel="preload" href="{{ $featured['image'] }}" as="image" fetchpriority="high">
    @endsection
@endif

@section('content')
    <article class="film-page">
        @if ($featured)
            <header class="film-hero">
                <div class="film-hero__media">
                    @if ($featured['video'])
                        <video autoplay muted loop playsinline preload="metadata" poster="{{ $featured['image'] }}"
                            aria-label="{{ $featured['title'] }} film by KAZEVIEW">
                            <source src="{{ $featured['video'] }}">
                        </video>
                    @else
                        <img src="{{ $featured['image'] }}" alt="{{ $featured['title'] }} film by KAZEVIEW"
                            style="object-position: {{ $featured['position'] }}" fetchpriority="high">
                    @endif
                </div>

                <div class="film-hero__shade" aria-hidden="true"></div>

                <div class="film-hero__content">
                    <p class="film-eyebrow">FEATURED FILM / {{ $featured['year'] }}</p>
                    <h1>{{ $featured['title'] }}<span class="accent">.</span></h1>

                    <div class="film-hero__meta">
                        <span>{{ $featured['category'] }}</span>
                        @if ($featured['duration'])
                            <span>{{ $featured['duration'] }}</span>
                        @endif
                    </div>

                    @if ($featured['link'])
                        <a class="film-watch-link" href="{{ $featured['link'] }}">
                            WATCH FILM
                            <span aria-hidden="true">↗</span>
                        </a>
                    @elseif ($featured['video'])
                        <button class="film-watch-link" type="button" data-film-play>
                            PLAY FILM
                            <span aria-hidden="true">▶</span>
                        </button>
                    @endif
                </div>

                <span class="film-hero__index" aria-hidden="true">01 / {{ str_pad((string) $filmItems->count(), 2, '0', STR_PAD_LEFT) }}</span>
            </header>

            <nav class="film-discovery" aria-label="Filter films">
                <div>
                    <p class="film-eyebrow">SELECTED MOTION WORK</p>
                    <p class="film-discovery__count">
                        <span data-film-count>{{ $filmItems->count() }}</span>
                        {{ \Illuminate\Support\Str::plural('FILM', $filmItems->count()) }}
                    </p>
                </div>

                <ul>
                    <li>
                        <button class="film-filter is-active" type="button" data-film-filter="all"
                            aria-pressed="true">ALL</button>
                    </li>
                    @foreach ($filmCategories as $category)
                        <li>
                            <button class="film-filter" type="button" data-film-filter="{{ $category['slug'] }}"
                                aria-pressed="false">{{ $category['label'] }}</button>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <section class="film-archive" aria-labelledby="film-archive-title">
                <div class="film-section-heading">
                    <p class="film-eyebrow">ARCHIVE / {{ now()->year }}</p>
                    <h2 id="film-archive-title">FILMOGRAPHY<span class="accent">.</span></h2>
                </div>

                <div class="film-grid" data-film-grid>
                    @foreach ($filmItems as $film)
                        <article class="film-card" data-film-category="{{ $film['category_slug'] }}">
                            <div class="film-card__media">
                                @if ($film['video'])
                                    <video muted loop playsinline preload="none" poster="{{ $film['image'] }}"
                                        aria-label="{{ $film['title'] }} film preview">
                                        <source data-src="{{ $film['video'] }}">
                                    </video>
                                @else
                                    <img src="{{ $film['image'] }}" alt="{{ $film['title'] }} film by KAZEVIEW"
                                        style="object-position: {{ $film['position'] }}"
                                        loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                                @endif

                                <span class="film-card__overlay" aria-hidden="true"></span>
                                @if ($film['video'])
                                    <button class="film-card__play" type="button"
                                        aria-label="Play preview for {{ $film['title'] }}" data-film-preview>
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M7 4.8v14.4L19 12 7 4.8Z" />
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <div class="film-card__content">
                                <div>
                                    <p>{{ $film['category'] }} / {{ $film['year'] }}</p>
                                    <h3>{{ $film['title'] }}</h3>
                                </div>

                                <div class="film-card__aside">
                                    @if ($film['duration'])
                                        <span>{{ $film['duration'] }}</span>
                                    @endif
                                    @if ($film['link'])
                                        <a href="{{ $film['link'] }}" aria-label="View {{ $film['title'] }} film">↗</a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="film-empty" data-film-empty role="status">
                    NO FILMS IN THIS CATEGORY.
                </div>
            </section>
        @else
            <section class="film-empty-state">
                <p class="film-eyebrow">KAZEVIEW FILMS</p>
                <h1>STORIES IN<br>MOTION<span class="accent">.</span></h1>
                <p>New films are currently in production.</p>
                <a href="{{ route('home.contact') }}">START A PROJECT <span aria-hidden="true">↗</span></a>
            </section>
        @endif
    </article>
@endsection

@if ($featured)
    @section('scripts')
        <script>
            (() => {
                const filters = [...document.querySelectorAll('[data-film-filter]')];
                const cards = [...document.querySelectorAll('[data-film-category]')];
                const count = document.querySelector('[data-film-count]');
                const empty = document.querySelector('[data-film-empty]');
                const heroVideo = document.querySelector('.film-hero video');
                const heroPlay = document.querySelector('[data-film-play]');

                filters.forEach((button) => {
                    button.addEventListener('click', () => {
                        const filter = button.dataset.filmFilter;
                        let visible = 0;

                        filters.forEach((item) => {
                            const active = item === button;
                            item.classList.toggle('is-active', active);
                            item.setAttribute('aria-pressed', String(active));
                        });

                        cards.forEach((card) => {
                            const show = filter === 'all' || card.dataset.filmCategory === filter;
                            card.classList.toggle('is-hidden', !show);
                            if (show) visible++;
                        });

                        if (count) count.textContent = String(visible);
                        if (empty) empty.classList.toggle('is-visible', visible === 0);
                    });
                });

                if (heroPlay && heroVideo) {
                    heroPlay.addEventListener('click', () => {
                        if (heroVideo.paused) {
                            heroVideo.muted = false;
                            heroVideo.controls = true;
                            heroVideo.play();
                            heroPlay.firstChild.textContent = 'PAUSE FILM ';
                        } else {
                            heroVideo.pause();
                            heroPlay.firstChild.textContent = 'PLAY FILM ';
                        }
                    });
                }

                document.querySelectorAll('[data-film-preview]').forEach((button) => {
                    const video = button.closest('.film-card__media')?.querySelector('video');
                    if (!video) return;

                    button.addEventListener('click', () => {
                        if (!video.currentSrc) {
                            const source = video.querySelector('source[data-src]');
                            if (source) {
                                source.src = source.dataset.src;
                                video.load();
                            }
                        }

                        document.querySelectorAll('.film-card video').forEach((otherVideo) => {
                            if (otherVideo !== video) {
                                otherVideo.pause();
                                otherVideo.closest('.film-card')?.classList.remove('is-playing');
                            }
                        });

                        if (video.paused) {
                            video.play();
                            button.closest('.film-card')?.classList.add('is-playing');
                        } else {
                            video.pause();
                            button.closest('.film-card')?.classList.remove('is-playing');
                        }
                    });
                });
            })();
        </script>
    @endsection
@endif