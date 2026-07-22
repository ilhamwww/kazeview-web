@extends('layouts.app')

@section('title', 'About — KAZEVIEW')
@section('meta_description', $about->intro ?: 'About KAZEVIEW — independent photography and film studio in Yogyakarta.')

@php
    $heroImagePath = $about->hero_image ?: $fallbackHeroImage;
    $storyImagePath = $about->story_image ?: $fallbackStoryImage;
    $heroImage = $heroImagePath ? asset('storage/' . $heroImagePath) : asset('KAZE_icon.png');
    $storyImage = $storyImagePath ? asset('storage/' . $storyImagePath) : $heroImage;

    $capabilities = collect($about->capabilities ?: \App\Models\AboutSetting::defaults()['capabilities'])
        ->filter(fn($item) => filled($item['title'] ?? null))
        ->values();

    $aboutWhatsApp = preg_replace('/\D+/', '', (string) ($data_web->wa ?? ''));
    if ($aboutWhatsApp !== '' && str_starts_with($aboutWhatsApp, '0')) {
        $aboutWhatsApp = '62' . substr($aboutWhatsApp, 1);
    } elseif ($aboutWhatsApp !== '' && !str_starts_with($aboutWhatsApp, '62')) {
        $aboutWhatsApp = '62' . $aboutWhatsApp;
    }

    $ctaUrl = $about->cta_url ?: ($aboutWhatsApp !== '' ? 'https://wa.me/' . $aboutWhatsApp : '#contact');
    $isExternalCta = str_starts_with($ctaUrl, 'http');
@endphp

@section('content')
    <article class="about-page">
        <header class="about-hero">
            <div class="about-hero__content">
                <p class="about-eyebrow">{{ $about->eyebrow }}</p>
                <h1>{{ $about->headline }}</h1>
                @if ($about->intro)
                    <p class="about-hero__intro">{{ $about->intro }}</p>
                @endif

                <dl class="about-identity">
                    <div>
                        <dt>BASE</dt>
                        <dd>{{ $about->location }}</dd>
                    </div>
                    @if ($about->established)
                        <div>
                            <dt>STUDIO</dt>
                            <dd>{{ $about->established }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <figure class="about-hero__media">
                <img src="{{ $heroImage }}" alt="{{ $about->headline }}" fetchpriority="high">
            </figure>
        </header>

        <section class="about-story" aria-labelledby="about-story-title">
            <figure class="about-story__media">
                <img src="{{ $storyImage }}" alt="{{ $about->story_title }}" loading="lazy">
            </figure>

            <div class="about-story__content">
                <p class="about-section-number" aria-hidden="true">01</p>
                <p class="about-eyebrow">OUR STORY</p>
                <h2 id="about-story-title">{{ $about->story_title }}</h2>
                @if ($about->story_body)
                    <div class="about-story__body">
                        @foreach (preg_split('/\r\n|\r|\n/', $about->story_body) as $paragraph)
                            @if (filled(trim($paragraph)))
                                <p>{{ trim($paragraph) }}</p>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="about-capabilities" aria-labelledby="about-capabilities-title">
            <div class="about-section-heading">
                <div>
                    <p class="about-section-number" aria-hidden="true">02</p>
                    <p class="about-eyebrow">WHAT WE DO</p>
                </div>
                <h2 id="about-capabilities-title">CAPABILITIES<span class="accent">.</span></h2>
            </div>

            <ol class="about-capability-list">
                @foreach ($capabilities as $capability)
                    <li>
                        <span class="about-capability-list__number">
                            {{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <h3>{{ $capability['title'] }}</h3>
                        @if (filled($capability['description'] ?? null))
                            <p>{{ $capability['description'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="about-cta" aria-labelledby="about-cta-title">
            <p class="about-eyebrow">START A PROJECT</p>
            <h2 id="about-cta-title">{{ $about->cta_title }}</h2>
            <a href="{{ $ctaUrl }}" @if ($isExternalCta) target="_blank" rel="noopener noreferrer" @endif>
                {{ $about->cta_label }}
                <span aria-hidden="true">↗</span>
            </a>
        </section>
    </article>
@endsection