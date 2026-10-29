@include('filament.streaming.partials.media-catalog', [
    'kind' => 'indonesia',
    'customLabel' => 'Indonesia',
    'trending' => $trending,
    'items' => $items,
    'hasMorePages' => $hasMorePages,
    'categories' => $categories,
    'category' => $category,
    'search' => $search,
    'activeCategoryLabel' => collect($categories)->firstWhere('id', $category)['label'] ?? ucfirst($category),
    'latestMovies' => $latestMovies,
    'latestSeries' => $latestSeries,
    'spotlightItems' => $spotlightItems,
])
