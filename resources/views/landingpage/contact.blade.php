@extends('layouts.app')

@section('title', 'Contact — KAZEVIEW')
@section('meta_description', $contact->intro ?: 'Contact KAZEVIEW for photography and film projects.')

@php
    $contactImagePath = $contact->image ?: $fallbackImage;
    $contactImage = $contactImagePath ? asset('storage/' . $contactImagePath) : asset('KAZE_icon.png');
    $whatsappUrl = $contact->whatsappUrl();
    $emailUrl = $contact->email ? 'mailto:' . $contact->email : null;

    $primaryUrl = $whatsappUrl ?: ($emailUrl ?: '#contact-details');
    $primaryExternal = str_starts_with($primaryUrl, 'http');

    $socialLinks = collect($contact->social_links ?? [])
        ->filter(
            fn($item) => filled($item['label'] ?? null)
                && filled($item['url'] ?? null),
        )
        ->values();
@endphp

@section('preloads')
    <link rel="preload" href="{{ $contactImage }}" as="image" fetchpriority="high">
@endsection

@section('content')
    <article class="contact-page">
        <header class="contact-hero">
            <div class="contact-hero__content">
                <p class="contact-eyebrow">{{ $contact->eyebrow }}</p>
                <h1>{{ $contact->headline }}</h1>

                @if ($contact->intro)
                    <p class="contact-hero__intro">{{ $contact->intro }}</p>
                @endif

                <a class="contact-primary-cta" href="{{ $primaryUrl }}"
                    @if ($primaryExternal) target="_blank" rel="noopener noreferrer" @endif>
                    {{ $contact->cta_label }}
                    <span aria-hidden="true">↗</span>
                </a>
            </div>

            <figure class="contact-hero__media">
                <img src="{{ $contactImage }}" alt="{{ $contact->headline }}" fetchpriority="high">
                <figcaption>
                    <span class="contact-status-dot" aria-hidden="true"></span>
                    {{ $contact->availability }}
                </figcaption>
            </figure>
        </header>

        <section class="contact-details" id="contact-details" aria-labelledby="contact-details-title">
            <div class="contact-details__heading">
                <p class="contact-eyebrow">DIRECT CONTACT</p>
                <h2 id="contact-details-title">GET IN<br>TOUCH<span class="accent">.</span></h2>
            </div>

            <dl class="contact-details__list">
                @if ($contact->email)
                    <div>
                        <dt>EMAIL</dt>
                        <dd>
                            <a href="{{ $emailUrl }}">{{ $contact->email }}</a>
                        </dd>
                    </div>
                @endif

                @if ($contact->whatsapp)
                    <div>
                        <dt>WHATSAPP</dt>
                        <dd>
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">
                                {{ $contact->whatsapp }}
                            </a>
                        </dd>
                    </div>
                @endif

                <div>
                    <dt>BASE</dt>
                    <dd>{{ $contact->location }}</dd>
                </div>

                <div>
                    <dt>RESPONSE</dt>
                    <dd>{{ $contact->response_time }}</dd>
                </div>
            </dl>
        </section>

        @if ($socialLinks->isNotEmpty())
            <nav class="contact-socials" aria-label="KAZEVIEW social profiles">
                <p class="contact-eyebrow">FOLLOW THE WORK</p>
                <ul>
                    @foreach ($socialLinks as $social)
                        <li>
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer">
                                <span>{{ $social['label'] }}</span>
                                <span aria-hidden="true">↗</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </article>
@endsection