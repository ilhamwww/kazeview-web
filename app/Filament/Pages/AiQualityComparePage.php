<?php

namespace App\Filament\Pages;

use App\Models\Content;
use Filament\Pages\Page;

class AiQualityComparePage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.ai-quality-compare';

    protected static ?string $title = 'AI vs Klasik';

    protected static ?string $slug = 'ai-quality-compare';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('lab')
                ->label('Buka Lab Upload')
                ->icon('fas-flask')
                ->url(fn () => url('/admin/ai-quality-lab')),
        ];
    }

    public $contentId = null;

    public $content = null;

    public static function getRoutePath(?string $panel = null): string
    {
        return '/ai-quality-compare/{id}';
    }

    public function mount($id): void
    {
        $this->contentId = (int) $id;
        $this->content = Content::query()->find($this->contentId);
    }

    public function getTitle(): string
    {
        $name = $this->content?->title ?? "Konten {$this->contentId}";

        return "AI vs Klasik — {$name}";
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/contents') => 'Contents',
            url('/admin/content-download-page/' . $this->contentId) => $this->content?->title ?? 'Download Data',
            'AI vs Klasik',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCompareData(): array
    {
        $path = storage_path("app/compare/content_{$this->contentId}.json");

        if (! is_file($path)) {
            return ['found' => false, 'photos' => [], 'summary' => [], 'generated_at' => null];
        }

        $raw = json_decode((string) file_get_contents($path), true);

        if (! is_array($raw)) {
            return ['found' => false, 'photos' => [], 'summary' => [], 'generated_at' => null];
        }

        $photos = array_values(array_filter(
            $raw['photos'] ?? [],
            fn ($p) => is_array($p) && ($p['ai_rating'] ?? -1) > 0,
        ));

        return [
            'found' => true,
            'generated_at' => $raw['generated_at'] ?? null,
            'total' => count($photos),
            'photos' => $photos,
            'summary' => $this->summarise($photos),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $photos
     * @return array<string, mixed>
     */
    protected function summarise(array $photos): array
    {
        if ($photos === []) {
            return [];
        }

        $col = fn (string $key): array => array_map(fn ($p) => (float) ($p[$key] ?? 0), $photos);

        $aiFocus = $col('ai_focus');
        $aiRating = $col('ai_rating');
        $classicRaw = $col('classic_sharp');
        $classicNorm = $col('classic_norm');
        $border = $col('border_ratio');
        $lum = $col('mean_lum');

        $aiCropCounts = ['none' => 0, 'minor' => 0, 'major' => 0, '' => 0];
        $dupGroups = [];
        $dark = 0;
        $bigGap = 0;

        foreach ($photos as $p) {
            $crop = (string) ($p['ai_crop'] ?? '');
            $aiCropCounts[$crop] = ($aiCropCounts[$crop] ?? 0) + 1;
            $dupGroups[(int) ($p['dup_group'] ?? 0)] = true;

            if ((float) ($p['mean_lum'] ?? 0) < 45) {
                $dark++;
            }

            if (abs((float) ($p['ai_rating'] ?? 0) - (float) ($p['classic_norm'] ?? 0)) >= 4) {
                $bigGap++;
            }
        }

        return [
            'total' => count($photos),
            'ai_focus_avg' => round(array_sum($aiFocus) / count($aiFocus), 2),
            'ai_focus_stdev' => round($this->stdev($aiFocus), 2),
            'ai_rating_avg' => round(array_sum($aiRating) / count($aiRating), 2),
            'ai_rating_stdev' => round($this->stdev($aiRating), 2),
            'classic_raw_stdev' => round($this->stdev($classicRaw), 2),
            'classic_norm_stdev' => round($this->stdev($classicNorm), 2),
            'spearman_raw' => round($this->spearman($classicRaw, $aiFocus), 2),
            'spearman_norm' => round($this->spearman($classicNorm, $aiFocus), 2),
            'border_high' => count(array_filter($border, fn ($v) => $v >= 0.95)),
            'border_mid' => count(array_filter($border, fn ($v) => $v >= 0.80 && $v < 0.95)),
            'ai_major' => $aiCropCounts['major'] ?? 0,
            'ai_minor' => $aiCropCounts['minor'] ?? 0,
            'ai_none' => $aiCropCounts['none'] ?? 0,
            'dup_groups' => count($dupGroups),
            'dup_extra' => count($photos) - count($dupGroups),
            'dark_photos' => $dark,
            'big_gap' => $bigGap,
            'lum_avg' => round(array_sum($lum) / count($lum), 1),
        ];
    }

    /**
     * @param  array<int, float>  $values
     */
    protected function stdev(array $values): float
    {
        $n = count($values);

        if ($n < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $n;
        $sum = 0.0;

        foreach ($values as $v) {
            $sum += ($v - $mean) ** 2;
        }

        return sqrt($sum / $n);
    }

    /**
     * Korelasi peringkat Spearman tanpa library tambahan.
     *
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    protected function spearman(array $a, array $b): float
    {
        $n = min(count($a), count($b));

        if ($n < 3) {
            return 0.0;
        }

        $rank = function (array $values): array {
            $idx = array_keys($values);
            usort($idx, fn ($x, $y) => $values[$x] <=> $values[$y]);
            $ranks = [];

            foreach ($idx as $pos => $key) {
                $ranks[$key] = $pos + 1;
            }

            ksort($ranks);

            return array_values($ranks);
        };

        $ra = $rank(array_slice($a, 0, $n));
        $rb = $rank(array_slice($b, 0, $n));
        $mean = ($n + 1) / 2;
        $num = 0.0;
        $da = 0.0;
        $db = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $num += ($ra[$i] - $mean) * ($rb[$i] - $mean);
            $da += ($ra[$i] - $mean) ** 2;
            $db += ($rb[$i] - $mean) ** 2;
        }

        return ($da * $db) > 0 ? $num / sqrt($da * $db) : 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'compare' => $this->getCompareData(),
            'contentTitle' => $this->content?->title ?? "Konten {$this->contentId}",
            'contentId' => (int) $this->contentId,
        ];
    }
}
