@include('filament.streaming.partials.media-catalog', [
    'kind' => 'series',
    'customLabel' => 'Drakor',
    'trending' => $trending,
    'items' => $items,
    'hasMorePages' => $hasMorePages,
])
