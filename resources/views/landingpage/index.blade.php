@extends('layouts.app')

@section('styles')
    <style>
        .masonry-gallery {
            display: flex;
            flex-wrap: wrap;
            margin: 0;
            padding: 0;
        }

        .masonry-gallery-item {
            overflow: hidden;
            flex-grow: 1;
            transition: opacity 1s ease, filter 1s ease, transform 1s ease;
            opacity: 0;
            filter: blur(10px);
            transform: translateY(20px);
        }

        .masonry-gallery-item.animate-in {
            opacity: 1;
            filter: blur(0);
            transform: translateY(0);
        }

        .masonry-gallery-item img {
            display: block;
            width: 100%;
            height: 301px;
            /* sama rata tinggi baris */
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .masonry-gallery-item:hover img {
            transform: scale(1.1);
        }

        /* Untuk menghilangkan gap antar foto */
        .masonry-gallery-item {
            margin: 0;
            padding: 0;
        }

        @media (max-width: 450px) {
            .masonry-gallery {
                flex-direction: column;
            }

            .masonry-gallery-item img {
                height: auto;
                width: 100%;
            }
        }

        .modal-backdrop {
            background-color: rgb(255, 255, 255) !important;
        }
    </style>
@endsection

@section('content')


    <!-- Section : Hero -->
    <section class="container " id="home">
        <div class="row align-items-center">
            <div class="col-lg-6 order-2 order-lg-1">
                <h6 class="text-uppercase"><span class="bg-black text-white px-1">Speed. Style. Story.</span></h6>
                <h1><strong>{{ $data_web->first_name ?? '' }}</strong>{{ $data_web->last_name ?? '' }}</h1>
                <p>Captures the thrill, emotion, and detail in every photograph.</p>
                <div>
                    <a href="http://wa.me/62{{ $data_web->wa ?? '' }}" target="_blank" class="btn btn-black border">
                        <i class="bi bi-whatsapp me-2"></i>
                        Whatsapp
                    </a>
                </div>
                <div class="mt-3">
                    <small>FOLLOW US:</small>
                    @if (count($data_links) > 0)
                        @foreach ($data_links as $item)
                            <a href="{{ $item['url'] }}" target="_blank" class="text-black ms-2">{{ $item['label'] }}</a>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="aspect-ratio-3-4">
                    <img src="{{ $data_web ? asset('storage/' . $data_web->hero_image) : '' }}" alt="About Image"
                        class="img-cover">
                </div>
            </div>
        </div>
    </section>


    <section class="mt-5 mb-5 d-flex justify-content-center">
        <h1 class="text-center"><strong>COLLECTIONS</strong></h1>
    </section>


    <section class="container-fluid p-0">
        <div class="masonry-gallery">
            @if (isset($galleryImages))
                @foreach ($galleryImages as $index => $image)
                    <div class="masonry-gallery-item" data-index="{{ $index }}">
                        <img src="{{ asset('storage/' . $image->image) }}" alt="Gallery Image"
                            data-index="{{ $index }}" class="gallery-image" />
                    </div>
                @endforeach
            @endif
        </div>
    </section>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body position-relative d-flex flex-column justify-content-center align-items-center p-3" style="overflow: hidden;">
                <!-- Tombol close -->
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"
                    aria-label="Close" style="width: 3rem; height: 3rem; aspect-ratio: 1; border-radius: 50%; background-color: #fff; color: #000;"></button>

                <!-- Tombol prev -->
                <button class="btn btn-light rounded-circle position-absolute start-0 top-50 translate-middle-y d-none d-md-block"
                    id="prevImage" style="width: 3rem; height: 3rem;">&larr;</button>

                <!-- Gambar -->
                <img id="modalImage" src="" class="img-fluid rounded mx-auto d-block" style="max-height: 90vh; object-fit: contain;">

                <!-- Tombol next -->
                <button class="btn btn-light rounded-circle position-absolute end-0 top-50 translate-middle-y d-none d-md-block"
                    id="nextImage" style="width: 3rem; height: 3rem;">&rarr;</button>
            </div>
        </div>
    </div>
</div>



@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            $('.gallery-item img').on('click', function() {
                var imgSrc = $(this).attr('src');
                $('#modalImage').attr('src', imgSrc);
                $('#imageModal').modal('show');
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            const $galleryImages = $('.gallery-image');
            let currentIndex = 0;

            function showModal(index) {
                const src = $galleryImages.eq(index).attr('src');
                $('#modalImage').attr('src', src);
                $('#imageModal').modal('show');
                currentIndex = index;
            }

            $galleryImages.on('click', function() {
                const index = $(this).data('index');
                showModal(index);
            });

            $('#prevImage').on('click', function() {
                currentIndex = (currentIndex - 1 + $galleryImages.length) % $galleryImages.length;
                showModal(currentIndex);
            });

            $('#nextImage').on('click', function() {
                currentIndex = (currentIndex + 1) % $galleryImages.length;
                showModal(currentIndex);
            });

            $(document).keydown(function(e) {
                if (!$('#imageModal').hasClass('show')) return;

                if (e.key === "ArrowLeft") {
                    $('#prevImage').click();
                } else if (e.key === "ArrowRight") {
                    $('#nextImage').click();
                }
            });
        });
    </script>

    <script>
        $(function() {
            $('.filter-btn').click(function() {
                var filter = $(this).data('filter');

                $('.filter-btn').removeClass('active');
                $(this).addClass('active');

                if (filter === 'all') {
                    $('.gallery-item').removeClass('hide');
                } else {
                    $('.gallery-item').each(function() {
                        if ($(this).hasClass(filter)) {
                            $(this).removeClass('hide');
                        } else {
                            $(this).addClass('hide');
                        }
                    });
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            const $items = $('.masonry-gallery-item');

            $items.each(function(index) {
                const $item = $(this);
                if (index < 5) {
                    setTimeout(() => {
                        $item.addClass('animate-in');
                    }, index * 100);
                }
            });

            function isInViewport($el) {
                const rect = $el[0].getBoundingClientRect();
                return rect.top < window.innerHeight - 100;
            }

            function checkAnimation() {
                $items.each(function(index) {
                    if (index < 5) return;

                    const $item = $(this);
                    if (!$item.hasClass('animate-in') && isInViewport($item)) {
                        $item.addClass('animate-in');
                    }
                });
            }

            $(window).on('scroll resize load', checkAnimation);
        });
    </script>
@endsection
