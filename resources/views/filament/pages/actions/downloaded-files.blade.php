<x-filament-widgets::widgets
    :widgets="[$downloadId => \App\Filament\Widgets\DownloadedFilesTable::class]"
    :columns="1"
    :data="[
        'downloadId' => $downloadId,
        'folderId' => $folderId,
    ]"
/>