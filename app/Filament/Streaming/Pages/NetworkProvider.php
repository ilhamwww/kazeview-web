<?php

namespace App\Filament\Streaming\Pages;

use App\Services\IndocastProviderService;
use Filament\Pages\Page;

/**
 * /network/{provider} — katalog ringkas satu penyedia.
 * weTV diarahkan ke halaman drama Asia yang sudah jalan.
 */
class NetworkProvider extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $title = 'Network - KZSTRM';
    protected static ?string $slug = 'network/{provider}';
    protected static string $view = 'filament.streaming.pages.network-provider';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $providerData = null;
    public array $items = [];
    public bool $playable = false;

    public function mount(string $provider): void
    {
        $found = null;

        foreach (Network::list() as $p) {
            if ($p['id'] === $provider) {
                $found = $p;
                break;
            }
        }

        if (!$found) {
            abort(404);
        }

        if (!empty($found['route'])) {
            redirect($found['route']);
        }

        $this->providerData = $found;
        $this->playable = ($found['status'] ?? '') === 'ready';

        $home = IndocastProviderService::home($found['api'] ?? $provider, 24);
        $this->items = $home['items'] ?? [];
    }

    public function getTitle(): string
    {
        return ($this->providerData['name'] ?? 'Network') . ' - KZSTRM';
    }
}
