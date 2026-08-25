@php
    $photoOffset = max(0, ($photos->firstItem() ?? 1) - 1);
@endphp

@foreach ($photos->getCollection() as $photo)
    @php
        $number = ++$photoOffset;
        $thumbnailUrl = route('photos.thumbnail', ['photo' => $photo->getKey()]);
        $displayName = pathinfo($photo->file_name, PATHINFO_FILENAME);
    @endphp
    <button class="photo-card" type="button"
        data-photo-src="{{ $thumbnailUrl }}"
        data-photo-name="{{ $displayName }}"
        data-photo-number="{{ $number }}"
        aria-label="Buka thumbnail foto {{ $number }}: {{ $displayName }}">
        <img src="{{ $thumbnailUrl }}"
            alt="{{ $displayName }}"
            loading="lazy"
            decoding="async"
            fetchpriority="low">
        <span class="photo-card__number">#{{ $number }} | {{ $displayName }}</span>
    </button>
@endforeach