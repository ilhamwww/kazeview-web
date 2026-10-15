@include('filament.streaming.partials.media-catalog', [
    'kind' => 'movie',
    'trending' => $trending,
    'items' => $movies,
    'hasMorePages' => $hasMorePages,
])
