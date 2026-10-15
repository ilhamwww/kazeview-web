@include('filament.streaming.partials.media-catalog', [
    'kind' => 'indonesia',
    'customLabel' => 'Indonesia',
    'trending' => $trending,
    'items' => $items,
    'hasMorePages' => $hasMorePages,
])
