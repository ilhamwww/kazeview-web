@php
    $photoOffset = max(0, ($photos->firstItem() ?? 1) - 1);
@endphp

@foreach ($photos->getCollection() as $photo)
    @php
        $number = ++$photoOffset;
        $thumbnailUrl = route('photos.thumbnail', ['photo' => $photo->getKey()]);
    @endphp
    <button class="photo-card" type="button"
        data-photo-src="{{ $thumbnailUrl }}"
        data-photo-name="{{ $photo->file_name }}"
        data-photo-number="{{ $number }}"
        aria-label="Buka thumbnail foto {{ $number }}: {{ $photo->file_name }}">
        <img src="{{ $thumbnailUrl }}"
            alt="{{ $photo->file_name }}"
            loading="lazy"
            decoding="async"
            fetchpriority="low">
        <span class="photo-card__number">#{{ str_pad((string) $number, 3, '0', STR_PAD_LEFT) }}</span>
    </button>
@endforeach