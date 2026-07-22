<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $data_web->name ?? 'KAZEVIEW')</title>
    <meta name="description"
        content="@yield('meta_description', $data_web->description ?? 'KAZEVIEW — Automotive, portrait, and event photography and films.')">
    <meta name="keywords"
        content="photography, film, automotive, motorsport, portrait, event, KAZEVIEW, Yogyakarta">
    <meta name="author" content="{{ $data_web->name ?? 'KAZEVIEW' }}">
    <meta name="robots" content="index, follow">

    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', $data_web->name ?? 'KAZEVIEW')">
    <meta property="og:description"
        content="@yield('meta_description', $data_web->description ?? 'KAZEVIEW — Automotive, portrait, and event photography and films.')">
    <meta property="og:image" content="{{ asset('KAZE_icon.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $data_web->name ?? 'KAZEVIEW' }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $data_web->name ?? 'KAZEVIEW')">
    <meta name="twitter:description"
        content="@yield('meta_description', $data_web->description ?? 'KAZEVIEW — Automotive, portrait, and event photography and films.')">
    <meta name="twitter:image" content="{{ asset('KAZE_icon.png') }}">

    <link rel="icon" type="image/png" href="{{ asset('KAZE_icon.png') }}">
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
    $isPreview = request()->routeIs('home.index_product');
    $isAbout = request()->routeIs('home.about');
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
    'page-preview' => $isPreview,
    'page-about' => $isAbout,
])>
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
            <a href="{{ route('home.index') }}#work" data-nav-section="work"
                @class(['is-active' => request()->routeIs('home.index')])>WORK</a>
            <a href="{{ route('home.index') }}#films" data-nav-section="films">FILMS</a>
            <a href="{{ route('home.index_product') }}" @class(['is-active' => $isPreview])
                @if ($isPreview) aria-current="page" @endif>PREVIEW</a>
            <a href="{{ route('home.about') }}" @class(['is-active' => request()->routeIs('home.about')])
                @if (request()->routeIs('home.about')) aria-current="page" @endif>ABOUT</a>
            <a href="{{ route('home.index') }}#contact" data-nav-section="contact">CONTACT</a>
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