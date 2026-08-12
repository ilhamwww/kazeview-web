<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $seoDefaults = \App\Models\WebsiteSetting::seoDefaults();
        $storedSeo = $data_web->seo_settings ?? [];
        if (is_string($storedSeo)) {
            $storedSeo = json_decode($storedSeo, true) ?: [];
        }
        $seo = array_replace_recursive($seoDefaults, is_array($storedSeo) ? $storedSeo : []);

        $siteName = trim((string) ($data_web->name ?? 'KAZEVIEW')) ?: 'KAZEVIEW';
        $defaultDescription = trim((string) ($data_web->description ?? ''))
            ?: 'KAZEVIEW — Automotive, portrait, and event photography and films.';
        $pageTitle = trim($__env->yieldContent('title'))
            ?: (trim((string) $seo['title']) ?: $siteName);
        $pageDescription = trim($__env->yieldContent('meta_description'))
            ?: (trim((string) $seo['description']) ?: $defaultDescription);
        $seoKeywords = trim((string) $seo['keywords'])
            ?: 'photography, film, automotive, motorsport, portrait, event, KAZEVIEW, Yogyakarta';
        $seoAuthor = trim((string) $seo['author']) ?: $siteName;
        $seoRobots = trim((string) $seo['robots']) ?: 'index, follow';

        $canonicalBase = rtrim(trim((string) $seo['canonical_url']) ?: config('app.url'), '/');
        $requestPath = request()->getPathInfo();
        $canonicalUrl = $canonicalBase . ($requestPath === '/' ? '/' : $requestPath);

        $seoImagePath = $data_web->seo_image ?? null;
        $socialImage = $seoImagePath
            ? asset('storage/' . $seoImagePath)
            : (!empty($data_web->hero_image)
                ? asset('storage/' . $data_web->hero_image)
                : asset('KAZE_icon.png'));

        $ogTitle = trim((string) $seo['og_title']) ?: $pageTitle;
        $ogDescription = trim((string) $seo['og_description']) ?: $pageDescription;
        $twitterTitle = trim((string) $seo['twitter_title']) ?: $ogTitle;
        $twitterDescription = trim((string) $seo['twitter_description']) ?: $ogDescription;
        $themeColor = trim((string) $seo['theme_color']) ?: '#050506';

        $rawSameAs = $seo['same_as'] ?? [];
        $sameAs = collect(is_array($rawSameAs) ? $rawSameAs : [])
            ->map(fn ($item) => is_array($item) ? ($item['url'] ?? null) : $item)
            ->filter(fn ($url) => is_string($url) && filter_var($url, FILTER_VALIDATE_URL))
            ->values()
            ->all();

        $organizationId = $canonicalBase . '/#organization';
        $websiteId = $canonicalBase . '/#website';
        $schemaGraph = [
            [
                '@type' => $seo['organization_type'] ?: 'ProfessionalService',
                '@id' => $organizationId,
                'name' => $siteName,
                'url' => $canonicalBase . '/',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => !empty($data_web->logo)
                        ? asset('storage/' . $data_web->logo)
                        : asset('KAZE_logo.png'),
                ],
                'image' => $socialImage,
                'description' => trim((string) $seo['organization_description']) ?: $defaultDescription,
                'sameAs' => $sameAs,
            ],
            [
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'url' => $canonicalBase . '/',
                'name' => $siteName,
                'description' => $defaultDescription,
                'publisher' => ['@id' => $organizationId],
                'inLanguage' => str_replace('_', '-', app()->getLocale()),
            ],
            [
                '@type' => 'WebPage',
                '@id' => $canonicalUrl . '#webpage',
                'url' => $canonicalUrl,
                'name' => $pageTitle,
                'description' => $pageDescription,
                'isPartOf' => ['@id' => $websiteId],
                'about' => ['@id' => $organizationId],
                'primaryImageOfPage' => [
                    '@type' => 'ImageObject',
                    'url' => $socialImage,
                ],
                'inLanguage' => str_replace('_', '-', app()->getLocale()),
            ],
        ];
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="author" content="{{ $seoAuthor }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <meta name="googlebot" content="{{ $seoRobots }}">
    <meta name="bingbot" content="{{ $seoRobots }}">
    <meta name="theme-color" content="{{ $themeColor }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    @if (filled($seo['google_verification']))
        <meta name="google-site-verification" content="{{ $seo['google_verification'] }}">
    @endif
    @if (filled($seo['bing_verification']))
        <meta name="msvalidate.01" content="{{ $seo['bing_verification'] }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image" content="{{ $socialImage }}">
    <meta property="og:image:alt" content="{{ $ogTitle }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

    <meta name="twitter:card" content="{{ $seo['twitter_card'] }}">
    <meta name="twitter:title" content="{{ $twitterTitle }}">
    <meta name="twitter:description" content="{{ $twitterDescription }}">
    <meta name="twitter:image" content="{{ $socialImage }}">
    <meta name="twitter:image:alt" content="{{ $twitterTitle }}">

    <script type="application/ld+json">
        @json(['@context' => 'https://schema.org', '@graph' => $schemaGraph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    </script>

    <link rel="icon" type="image/png" href="{{ asset('KAZE_icon.png') }}">

    @php
        $headLogoPath = !empty($data_web->logo)
            ? asset('storage/' . $data_web->logo)
            : asset('KAZE_logo.png');
    @endphp
    <link rel="preload" href="{{ $headLogoPath }}" as="image" fetchpriority="high">
    @yield('preloads')

    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Inter+Tight:wght@600;700;800&display=swap"
        rel="stylesheet">
    <link href="{{ asset('css/landingpage/kazeview.css') }}?v={{ file_exists(public_path('css/landingpage/kazeview.css')) ? filemtime(public_path('css/landingpage/kazeview.css')) : time() }}"
        rel="stylesheet">

    @yield('styles')
</head>

@php
    $isHome = request()->routeIs('home.index');
    $isFilms = request()->routeIs('home.films');
    $isPreview = request()->routeIs('home.index_product', 'preview.show');
    $isAbout = request()->routeIs('home.about');
    $isContact = request()->routeIs('home.contact');
    $whatsAppNumber = preg_replace('/\D+/', '', (string) ($data_web->wa ?? ''));
    if ($whatsAppNumber !== '' && str_starts_with($whatsAppNumber, '0')) {
        $whatsAppNumber = '62' . substr($whatsAppNumber, 1);
    } elseif ($whatsAppNumber !== '' && !str_starts_with($whatsAppNumber, '62')) {
        $whatsAppNumber = '62' . $whatsAppNumber;
    }
    $whatsAppUrl = $whatsAppNumber !== '' ? 'https://wa.me/' . $whatsAppNumber : '#contact';
    $hasCustomLogo = !empty($data_web->logo);
    $logoPath = $hasCustomLogo ? asset('storage/' . $data_web->logo) : asset('KAZE_logo.png');
@endphp

<body @class([
    'page-home' => $isHome,
    'page-films' => $isFilms,
    'page-preview' => $isPreview,
    'page-about' => $isAbout,
    'page-contact' => $isContact,
])>
    <div class="site-preloader" id="site-preloader" aria-hidden="true">
        <div class="site-preloader__brand">
            <img src="{{ $logoPath }}" alt="" width="174" height="24">
        </div>
        <div class="site-preloader__line" aria-hidden="true">
            <span></span>
        </div>
    </div>

    <a class="skip-link" href="#main-content">Skip to content</a>

    <header class="site-header {{ $isPreview ? 'site-header--preview' : 'site-header--home' }}">
        <a class="site-logo {{ $hasCustomLogo ? 'site-logo--custom' : '' }}" href="{{ route('home.index') }}"
            aria-label="KAZEVIEW home">
            <img src="{{ $logoPath }}" alt="KAZEVIEW" width="174" height="24">
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation">
            <span class="sr-only">Toggle navigation</span>
            <span></span>
            <span></span>
        </button>

        <nav class="site-nav" id="primary-navigation" aria-label="Primary navigation">
            <a href="{{ route('home.index') }}" data-nav-section="work"
                @class(['is-active' => request()->routeIs('home.index')])>WORK</a>
            <a href="{{ route('home.films') }}" @class(['is-active' => $isFilms])
                @if ($isFilms) aria-current="page" @endif>FILMS</a>
            <a href="{{ route('home.index_product') }}" @class(['is-active' => $isPreview])
                @if ($isPreview) aria-current="page" @endif>PREVIEW</a>
            <a href="{{ route('home.about') }}" @class(['is-active' => request()->routeIs('home.about')])
                @if (request()->routeIs('home.about')) aria-current="page" @endif>ABOUT</a>
            <a href="{{ route('home.contact') }}" @class(['is-active' => $isContact])
                @if ($isContact) aria-current="page" @endif>CONTACT</a>
            <a class="site-nav__book" href="{{ $whatsAppUrl }}" @if ($whatsAppNumber !== '') target="_blank"
                rel="noopener noreferrer" @endif>BOOK A SHOOT</a>
        </nav>
    </header>

    <main id="main-content">
        @yield('content')
    </main>

    <footer class="site-footer" id="contact">
        <span>© {{ date('Y') }} {{ strtoupper($data_web->name ?? 'KAZEVIEW') }}</span>
        <span>PHOTO + FILM / YOGYAKARTA</span>
    </footer>

    @yield('scripts')
    <script>
        (() => {
            const preloader = document.querySelector('#site-preloader');
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const preloaderStartedAt = performance.now();
            const minimumPreloaderDuration = 1100;
            let preloaderFinished = false;

            const finishLoading = () => {
                if (!preloader || preloaderFinished) {
                    return;
                }

                preloaderFinished = true;

                const elapsed = performance.now() - preloaderStartedAt;
                const remaining = reduceMotion
                    ? 0
                    : Math.max(0, minimumPreloaderDuration - elapsed);

                window.setTimeout(() => {
                    preloader.classList.add('is-loaded');
                    window.setTimeout(() => preloader.remove(), reduceMotion ? 0 : 700);
                }, remaining);
            };

            if (document.readyState === 'complete') {
                finishLoading();
            } else {
                window.addEventListener('load', finishLoading, { once: true });
            }

            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    finishLoading();
                }
            }, { once: true });

            window.setTimeout(finishLoading, 5000);

            const revealGroups = [
                {
                    selector: '.home-featured-grid, .film-hero, .preview-intro, .about-hero, .contact-hero',
                    className: 'reveal-cinematic',
                },
                {
                    selector: '.home-filter-bar, .film-discovery, .film-section-heading, .discovery-bar, .about-story, .about-section-heading, .contact-details__heading',
                    className: 'reveal-up',
                },
                {
                    selector: '.home-portfolio-card, .film-card, .event-card, .about-capability-list > li, .contact-details__list > div, .contact-socials li',
                    className: 'reveal-up',
                    stagger: true,
                },
                {
                    selector: '.about-story__media, .about-hero__media, .contact-hero__media',
                    className: 'reveal-media',
                },
                {
                    selector: '.about-story__content, .about-cta, .contact-details, .contact-socials',
                    className: 'reveal-up',
                },
            ];

            const revealElements = [];

            revealGroups.forEach((group) => {
                document.querySelectorAll(group.selector).forEach((element, index) => {
                    element.classList.add('reveal', group.className);

                    if (group.stagger) {
                        element.style.setProperty('--reveal-delay', `${Math.min(index % 8, 7) * 65}ms`);
                    }

                    revealElements.push(element);
                });
            });

            if (reduceMotion || !('IntersectionObserver' in window)) {
                revealElements.forEach((element) => element.classList.add('is-revealed'));
            } else {
                const observer = new IntersectionObserver((entries, activeObserver) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        entry.target.classList.add('is-revealed');
                        activeObserver.unobserve(entry.target);
                    });
                }, {
                    rootMargin: '0px 0px -8% 0px',
                    threshold: 0.08,
                });

                revealElements.forEach((element) => observer.observe(element));
            }

            requestAnimationFrame(() => {
                document.body.classList.add('page-is-ready');
            });

            const toggle = document.querySelector('.menu-toggle');
            const nav = document.querySelector('.site-nav');

            if (!toggle || !nav) return;

            const closeMenu = () => {
                document.body.classList.remove('menu-open');
                toggle.setAttribute('aria-expanded', 'false');
            };

            toggle.addEventListener('click', () => {
                const open = document.body.classList.toggle('menu-open');
                toggle.setAttribute('aria-expanded', String(open));
            });

            const sectionLinks = [...nav.querySelectorAll('[data-nav-section]')];

            const setActiveSection = () => {
                if (!document.body.classList.contains('page-home') || sectionLinks.length === 0) {
                    return;
                }

                const activeSection = window.location.hash.replace('#', '') || 'work';

                sectionLinks.forEach((link) => {
                    const active = link.dataset.navSection === activeSection;
                    link.classList.toggle('is-active', active);

                    if (active) {
                        link.setAttribute('aria-current', 'page');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });
            };

            nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
                closeMenu();
                window.setTimeout(setActiveSection, 0);
            }));

            window.addEventListener('hashchange', setActiveSection);
            setActiveSection();

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeMenu();
                    toggle.focus();
                }
            });
        })();
    </script>
</body>

</html>