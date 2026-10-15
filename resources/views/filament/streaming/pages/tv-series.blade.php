@include('filament.streaming.partials.media-catalog', [
    'kind' => 'series',
    'trending' => $trending,
    'items' => $series,
    'hasMorePages' => $hasMorePages,
])
