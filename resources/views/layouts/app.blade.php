<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $data_web->name ?? 'KAZEVIEW' }}</title>
    <meta name="description"
        content="{{ $data_web->description ?? 'Kazeview - Portofolio Fotografi, Cosplay, Otomotif, Potret, dan Momen Spesial.' }}">
    <meta name="keywords"
        content="fotografi, photography, Kazeview, cosplay, otomotif, potret, jasa foto, Bantul, Yogyakarta">
    <meta name="author" content="{{ $data_web->name ?? 'KAZEVIEW' }}">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $data_web->name ?? 'KAZEVIEW' }}">
    <meta property="og:description"
        content="{{ $data_web->description ?? 'Kazeview - Portofolio Fotografi, Cosplay, Otomotif, Potret, dan Momen Spesial.' }}">
    <meta property="og:image" content="{{ asset('KAZE_icon.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $data_web->name ?? 'KAZEVIEW' }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $data_web->name ?? 'KAZEVIEW' }}">
    <meta name="twitter:description"
        content="{{ $data_web->description ?? 'Kazeview - Portofolio Fotografi, Cosplay, Otomotif, Potret, dan Momen Spesial.' }}">
    <meta name="twitter:image" content="{{ asset('KAZE_icon.png') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('KAZE_icon.png') }}" />

    <!-- Stylesheets -->
    <link href="{{ asset('css/landingpage/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/landingpage/custom.css') }}?v={{ time() }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    @yield('styles')
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex: 1;
        }

        @media (max-width: 450px) {
            .main-pages {
                padding-top: 2rem !important;
            }
        }
    </style>
</head>


<body>
    <script>
        document.addEventListener('contextmenu', event => event.preventDefault());

        document.addEventListener('touchstart', function(e) {
            e.target.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });
        }, {
            passive: false
        });

        document.querySelectorAll('img').forEach(img => {
            img.setAttribute('draggable', 'false');
            img.addEventListener('dragstart', e => e.preventDefault());
        });
    </script>

    <div id="loading-screen">
        <div class="loading-content wave-container">
            <span class="">{{ $data_web->name ?? '' }}...</span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="{{ asset('storage/' . ($data_web->logo ?? '')) }}" alt="Logo"
                    class="d-inline-block align-top" height="30rem">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="filter: invert(1)"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/preview">Preview</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-pages" style="padding-top: 5rem;">
        @yield('content')
    </div>
    <!-- Footer -->
    <footer class="footer mt-auto bg-dark text-white py-3">
        <div class="container text-center">
            <p>&copy; {{ date('Y') }} {{ $data_web->name ?? '' }}. All rights reserved.</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @yield('scripts')
    <script>
        $(window).on('load', function() {
            $('#loading-screen').addClass('hide');
        });

        $(function() {
            function isInViewport($el) {
                var elementTop = $el.offset().top;
                var elementBottom = elementTop + $el.outerHeight();
                var viewportTop = $(window).scrollTop();
                var viewportBottom = viewportTop + $(window).height();
                return elementBottom > viewportTop + 100 && elementTop < viewportBottom - 100;
            }

            function checkCards() {
                $('.product-card').each(function() {
                    var $card = $(this);
                    if ($card.hasClass('animate-in')) return;
                    if (isInViewport($card)) {
                        $card.addClass('animate-in');
                    }
                });
            }

            $(window).on('scroll resize load', checkCards);
        });
    </script>
</body>

</html>
