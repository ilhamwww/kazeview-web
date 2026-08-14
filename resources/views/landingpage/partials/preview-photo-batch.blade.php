@php
    $photoOffset = max(0, ($photos->firstItem() ?? 1) - 1);
@endphp

@foreach ($photos->getCollection() as $photo)
    @php $number = ++$photoOffset; @endphp
    <button class="photo-card" type="button"
        data-photo-src="{{ $photo->public_url }}"
        data-photo-name="{{ $photo->file_name }}"
        data-photo-number="{{ $number }}"
        aria-label="Buka foto {{ $number }}: {{ $photo->file_name }}">
        <img src="{{ route('photos.thumbnail', ['photo' => $photo->getKey()]) }}"
            alt="{{ $photo->file_name }}"
            loading="lazy"
            decoding="async"
            fetchpriority="low"
            onerror="this.onerror=null;this.src=@js($photo->public_url)">
        <span class="photo-card__number">#{{ str_pad((string) $number, 3, '0', STR_PAD_LEFT) }}</span>
    </button>
@endforeach