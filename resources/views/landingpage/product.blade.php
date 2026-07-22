@extends('layouts.app')

@section('title', 'Preview Event Galleries — KAZEVIEW')
@section('meta_description', 'Find your KAZEVIEW event gallery, preview your photos and films, then pay and download.')

@php
    $fallbackImage = !empty($data_web->hero_image)
        ? asset('storage/' . $data_web->hero_image)
        : asset('KAZE_icon.png');

    $events = collect($data_konten ?? [])
        ->filter(fn($item) => !empty($item->image))
        ->map(function ($item) use ($fallbackImage) {
            $type = in_array($item->preview_type ?? null, ['PHOTOGRAPHY', 'PHOTO + FILM'], true)
                ? $item->preview_type
                : 'PHOTOGRAPHY';
            $category = strtolower(trim((string) ($item->preview_category ?? '')));
            if ($type === 'PHOTO + FILM' && !str_contains($category, 'film')) {
                $category = trim($category . ' film');
            }

            $rawPosition = trim((string) ($item->image_position ?? '50% 50%'));
            $position = preg_match('/^(left|center|right|\d{1,3}%)(\s+(top|center|bottom|\d{1,3}%))?$/', $rawPosition)
                ? $rawPosition
                : '50% 50%';

            $price = !empty($item->is_price_enabled) && is_numeric($item->price ?? null)
                ? 'Rp ' . number_format((float) $item->price, 0, ',', '.') . ' / PHOTO'
                : 'FREE DOWNLOAD';

            return [
                'id' => $item->id,
                'title' => strtoupper(trim((string) ($item->title ?? 'UNTITLED EVENT'))),
                'location' => strtoupper(trim((string) ($item->event_location ?? 'LOCATION TBA'))),
                'date' => !empty($item->event_date)
                    ? \Illuminate\Support\Carbon::parse($item->event_date)->format('Y-m-d')
                    : optional($item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at) : null)->format('Y-m-d'),
                'formatted_date' => !empty($item->event_date)
                    ? \Illuminate\Support\Carbon::parse($item->event_date)->format('d M Y')
                    : '',
                'type' => $type,
                'category' => $category,
                'is_new' => (bool) ($item->is_new ?? false),
                'position' => $position,
                'duration' => $item->film_duration ?? null,
                'price' => $price,
                'image' => !empty($item->image) ? asset('storage/' . $item->image) : $fallbackImage,
                'link' => !empty($item->link) ? $item->link : '#',
            ];
        })
        ->values();

    $whatsAppNumber = preg_replace('/\D+/', '', (string) ($data_web->wa ?? ''));
    if ($whatsAppNumber !== '' && str_starts_with($whatsAppNumber, '0')) {
        $whatsAppNumber = '62' . substr($whatsAppNumber, 1);
    } elseif ($whatsAppNumber !== '' && !str_starts_with($whatsAppNumber, '62')) {
        $whatsAppNumber = '62' . $whatsAppNumber;
    }
    $helpUrl = $whatsAppNumber !== '' ? 'https://wa.me/' . $whatsAppNumber : '#contact';
@endphp

@section('content')
    <section class="preview-intro" aria-labelledby="preview-heading">
        <div>
            <p class="preview-eyebrow">EVENT GALLERIES / 2026</p>
            <h1 class="preview-title" id="preview-heading">FIND YOUR<br>MOMENT<span class="accent">.</span></h1>
            <p class="preview-description">Browse the event. Preview your shot. Make it yours.</p>
        </div>

        <div class="preview-process" aria-label="Purchase process">
            <div class="process-steps">
                <div class="process-step">
                    <span class="process-step__number">01</span>
                    <span class="process-step__label">SELECT EVENT</span>
                </div>
                <div class="process-step">
                    <span class="process-step__number">02</span>
                    <span class="process-step__label">PREVIEW PHOTOS</span>
                </div>
                <div class="process-step">
                    <span class="process-step__number">03</span>
                    <span class="process-step__label">PAY & DOWNLOAD</span>
                </div>
            </div>

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
                <a class="help-link" href="{{ $helpUrl }}" @if ($whatsAppNumber !== '') target="_blank"
                    rel="noopener noreferrer" @endif>
                    <svg class="icon-outline" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 11.5a8.4 8.4 0 0 1-9 8.5 9.4 9.4 0 0 1-3.8-.8L3 21l1.7-5A8.5 8.5 0 1 1 21 11.5Z" />
                        <path d="M8.4 8.2c.3 3.1 2.3 5 5.4 5.4" />
                    </svg>
                    NEED HELP?
                </a>
            </div>
        </div>
    </section>

    <section class="discovery-bar" aria-label="Discover event galleries">
        <label class="event-search">
            <span class="sr-only">Search event, place, or date</span>
            <svg class="icon-outline" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-4-4" />
            </svg>
            <input id="event-search-input" type="search" placeholder="Search event, place, or date"
                autocomplete="off">
            <button class="search-clear" type="button" aria-label="Clear search" title="Clear search">×</button>
        </label>

        <div class="discovery-filters" role="group" aria-label="Filter events">
            @foreach (['all' => 'ALL EVENTS', 'latest' => 'LATEST', 'motorcycle' => 'MOTORCYCLE', 'automotive' => 'AUTOMOTIVE', 'film' => 'WITH FILM'] as $value => $label)
                <button class="discovery-filter {{ $loop->first ? 'is-active' : '' }}" type="button"
                    data-event-filter="{{ $value }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <label class="event-sort">
            <span class="sr-only">Sort events</span>
            <select id="event-sort-select">
                <option value="newest">NEWEST FIRST</option>
                <option value="oldest">OLDEST FIRST</option>
            </select>
            <svg class="icon-outline" viewBox="0 0 24 24" aria-hidden="true">
                <path d="m7 9 5 5 5-5" />
            </svg>
        </label>
    </section>

    <section class="event-grid" id="event-grid" aria-label="Event galleries" aria-live="polite">
        @foreach ($events as $event)
            <a class="event-card" href="{{ $event['link'] }}"
                data-title="{{ strtolower($event['title']) }}" data-location="{{ strtolower($event['location']) }}"
                data-date="{{ $event['date'] }}" data-formatted-date="{{ strtolower($event['formatted_date']) }}"
                data-category="{{ $event['category'] }}" data-is-new="{{ $event['is_new'] ? 'true' : 'false' }}"
                aria-label="View {{ $event['title'] }} gallery at {{ $event['location'] }}">
                <img class="event-card__media" src="{{ $event['image'] }}"
                    alt="{{ $event['title'] }} at {{ $event['location'] }}"
                    style="object-position: {{ $event['position'] }}"
                    loading="{{ $loop->index < 4 ? 'eager' : 'lazy' }}">
                <span class="event-card__overlay" aria-hidden="true"></span>

                @if ($event['is_new'])
                    <span class="event-card__new" aria-hidden="true">NEW</span>
                @endif

                <span class="event-card__content" aria-hidden="true">
                    <span class="event-card__type">
                        @if ($event['type'] === 'PHOTO + FILM')
                            <span class="event-card__mini-play">
                                <svg viewBox="0 0 24 24">
                                    <path d="M7 4.8v14.4L19 12 7 4.8Z" />
                                </svg>
                            </span>
                        @endif
                        <span class="event-card__type-text">
                            {{ $event['type'] }}
                            @if ($event['type'] === 'PHOTO + FILM' && $event['duration'])
                                · {{ $event['duration'] }}
                            @endif
                        </span>
                    </span>
                    <span class="event-card__title">{{ $event['title'] }}</span>
                    <span class="event-card__location">
                        <svg class="icon-outline" viewBox="0 0 24 24">
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" />
                            <circle cx="12" cy="10" r="2.5" />
                        </svg>
                        {{ $event['location'] }}
                    </span>
                    <span class="event-card__price">{{ $event['price'] }}</span>
                </span>

                <span class="event-card__cta" aria-hidden="true">
                    VIEW GALLERY
                    <svg class="icon-outline" viewBox="0 0 24 24">
                        <path d="M5 12h14M14 7l5 5-5 5" />
                    </svg>
                </span>
            </a>
        @endforeach
    </section>

    <div class="event-empty" id="event-empty" role="status">
        <p>NO EVENTS MATCH YOUR SEARCH.</p>
    </div>

    <div class="payment-modal" id="payment-modal" role="dialog" aria-modal="true"
        aria-labelledby="payment-modal-title" aria-hidden="true">
        <div class="payment-modal__dialog">
            <button class="payment-modal__close" type="button" data-close-payment aria-label="Close payment modal">×</button>
            <h2 id="payment-modal-title">PAY / QRIS</h2>
            <p>Scan the QRIS code, complete your payment, then send the payment confirmation and selected photo
                details to KAZEVIEW via WhatsApp.</p>
            <img src="{{ asset('QRIS.png') }}" alt="KAZEVIEW QRIS payment code">
            <a class="payment-modal__help" href="{{ $helpUrl }}" @if ($whatsAppNumber !== '') target="_blank"
                rel="noopener noreferrer" @endif>
                <svg class="icon-outline" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 11.5a8.4 8.4 0 0 1-9 8.5 9.4 9.4 0 0 1-3.8-.8L3 21l1.7-5A8.5 8.5 0 1 1 21 11.5Z" />
                </svg>
                CONFIRM VIA WHATSAPP
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (() => {
            const search = document.querySelector('#event-search-input');
            const clear = document.querySelector('.search-clear');
            const filters = [...document.querySelectorAll('[data-event-filter]')];
            const sort = document.querySelector('#event-sort-select');
            const grid = document.querySelector('#event-grid');
            const empty = document.querySelector('#event-empty');
            const modal = document.querySelector('#payment-modal');
            const openModal = document.querySelector('[data-open-payment]');
            const closeModal = document.querySelector('[data-close-payment]');
            let activeFilter = 'all';
            let searchTimer;
            let lastFocusedElement;

            const cards = () => [...grid.querySelectorAll('.event-card')];

            const matchesFilter = (card) => {
                const category = card.dataset.category;
                if (activeFilter === 'all') return true;
                if (activeFilter === 'latest') return card.dataset.isNew === 'true';
                return category.split(' ').includes(activeFilter);
            };

            const applyFilters = () => {
                const term = search.value.trim().toLowerCase();
                let visibleCount = 0;

                cards().forEach((card) => card.classList.add('is-filtering'));

                window.setTimeout(() => {
                    cards().forEach((card) => {
                        const haystack =
                            `${card.dataset.title} ${card.dataset.location} ${card.dataset.date} ${card.dataset.formattedDate}`;
                        const visible = haystack.includes(term) && matchesFilter(card);
                        card.classList.toggle('is-hidden', !visible);
                        card.classList.remove('is-filtering');
                        if (visible) visibleCount++;
                    });

                    clear.classList.toggle('is-visible', term.length > 0);
                    empty.classList.toggle('is-visible', visibleCount === 0);
                }, 160);
            };

            const queueSearch = () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(applyFilters, 250);
            };

            search.addEventListener('input', queueSearch);
            clear.addEventListener('click', () => {
                search.value = '';
                search.focus();
                applyFilters();
            });

            filters.forEach((button) => {
                button.addEventListener('click', () => {
                    activeFilter = button.dataset.eventFilter;
                    filters.forEach((item) => {
                        const active = item === button;
                        item.classList.toggle('is-active', active);
                        item.setAttribute('aria-pressed', String(active));
                    });
                    applyFilters();
                });
            });

            sort.addEventListener('change', () => {
                const sorted = cards().sort((a, b) => {
                    const comparison = a.dataset.date.localeCompare(b.dataset.date);
                    return sort.value === 'oldest' ? comparison : -comparison;
                });
                sorted.forEach((card) => grid.appendChild(card));
            });

            const setModal = (open) => {
                modal.classList.toggle('is-open', open);
                modal.setAttribute('aria-hidden', String(!open));
                document.body.style.overflow = open ? 'hidden' : '';

                if (open) {
                    lastFocusedElement = document.activeElement;
                    closeModal.focus();
                } else if (lastFocusedElement) {
                    lastFocusedElement.focus();
                }
            };

            openModal.addEventListener('click', () => setModal(true));
            closeModal.addEventListener('click', () => setModal(false));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) setModal(false);
            });

            modal.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setModal(false);
                    return;
                }

                if (event.key === 'Tab') {
                    const focusable = [...modal.querySelectorAll('button, a[href]')];
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });
        })();
    </script>
@endsection