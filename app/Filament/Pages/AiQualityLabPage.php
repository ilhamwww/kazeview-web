<?php

namespace App\Filament\Pages;

use App\Services\NineRouterAiService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

/**
 * Lab eksperimen: unggah satu gambar, lalu bandingkan penilaian kualitas
 * secara klasik (tanpa AI) dengan penilaian model AI.
 *
 * Metrik klasik dihitung dengan GD murni di PHP — tidak memanggil shell,
 * tidak butuh library tambahan.
 */
class AiQualityLabPage extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'fas-flask';

    protected static ?string $navigationLabel = 'Lab Klasik vs AI';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'ai-quality-lab';

    protected static string $view = 'filament.pages.ai-quality-lab';

    protected static ?string $title = 'Lab: Klasik vs AI';

    /**
     * Kalibrasi skor dari 265 foto konten 68, dianalisis pada 512 px.
     * Skor utama memakai ketajaman area tengah (norm_center) karena subjek
     * motor biasanya berada di tengah, jadi lebih mewakili ketajaman subjek.
     */
    protected const CEN_P5 = 0.2688;

    protected const CEN_P95 = 0.8256;

    /** Kalibrasi untuk metrik seluruh frame (pembanding). */
    protected const WHOLE_P5 = 0.1436;

    protected const WHOLE_P95 = 0.7269;

    public $upload = null;

    public bool $fullAnalysis = false;

    public ?array $classic = null;

    public ?array $ai = null;

    public ?array $descriptor = null;

    public ?string $previewUrl = null;

    public ?string $fileName = null;

    public ?string $error = null;

    public ?float $classicSeconds = null;

    public ?float $aiSeconds = null;

    public function mount(): void
    {
        //
    }

    public function reset3(): void
    {
        $this->reset(['upload', 'classic', 'ai', 'descriptor', 'previewUrl', 'fileName', 'error', 'classicSeconds', 'aiSeconds']);
    }

    public function analyse(NineRouterAiService $ai): void
    {
        $this->reset(['classic', 'ai', 'descriptor', 'previewUrl', 'fileName', 'error', 'classicSeconds', 'aiSeconds']);

        $this->validate([
            'upload' => 'required|file|mimes:jpg,jpeg,png,webp|max:25600',
        ], [
            'upload.required' => 'Pilih gambar dulu.',
            'upload.mimes' => 'Format harus JPG, PNG, atau WEBP.',
            'upload.max' => 'Ukuran maksimal 25 MB.',
        ]);

        $temporaryPath = $this->upload->getRealPath();

        if (! $temporaryPath || ! is_file($temporaryPath)) {
            $this->error = 'Berkas unggahan tidak terbaca.';

            return;
        }

        $this->runAnalysis($ai, $temporaryPath, (string) $this->upload->getClientOriginalName());
    }

    /**
     * Inti analisis, dipisah dari validasi unggahan agar bisa diuji langsung.
     */
    public function runAnalysis(NineRouterAiService $ai, string $temporaryPath, string $originalName): void
    {
        $this->fileName = $originalName;

        // Simpan salinan permanen untuk pratinjau.
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg');
        $stored = 'lab/' . now()->format('Ymd-His') . '-' . substr(sha1($this->fileName . microtime()), 0, 8)
            . '.' . $extension;

        Storage::disk('public')->put($stored, file_get_contents($temporaryPath));
        $this->previewUrl = Storage::disk('public')->url($stored);
        $absolute = Storage::disk('public')->path($stored);

        // 1) Metrik klasik
        $t0 = microtime(true);

        try {
            $this->classic = $this->classicMetrics($absolute);
        } catch (Throwable $e) {
            $this->error = 'Gagal menghitung metrik klasik: ' . $e->getMessage();
        }

        $this->classicSeconds = round(microtime(true) - $t0, 2);

        // 2) Penilaian AI
        if (! $ai->configured()) {
            $this->error = ($this->error ? $this->error . ' ' : '') . 'Kunci AI belum dikonfigurasi.';

            return;
        }

        $t1 = microtime(true);

        try {
            $quality = $ai->assessQuality($absolute);

            $this->ai = $quality + [
                'model' => $ai->visionModel(),
            ];

            if ($this->fullAnalysis) {
                $this->descriptor = $ai->describeMotorcycle($absolute);
            }
        } catch (Throwable $e) {
            $this->error = ($this->error ? $this->error . ' ' : '')
                . 'Penilaian AI gagal: ' . substr($e->getMessage(), 0, 200);
        }

        $this->aiSeconds = round(microtime(true) - $t1, 2);
    }

    /**
     * Metrik kualitas klasik memakai GD. Semua nilai dihitung pada salinan
     * maksimal 512 px agar cepat namun tetap mewakili detail.
     *
     * @return array<string, mixed>
     */
    protected function classicMetrics(string $path): array
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('Ekstensi GD tidak tersedia.');
        }

        $info = @getimagesize($path);

        if ($info === false) {
            throw new RuntimeException('Berkas bukan gambar yang valid.');
        }

        [$origW, $origH] = $info;

        $contents = file_get_contents($path);
        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('Gambar tidak dapat dibuka.');
        }

        $scale = min(1, 512 / max($origW, $origH));
        $w = max(1, (int) round($origW * $scale));
        $h = max(1, (int) round($origH * $scale));

        $canvas = imagecreatetruecolor($w, $h);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $w, $h, $origW, $origH);

        // Matriks luminance
        $lum = [];
        $sum = 0.0;
        $sumSq = 0.0;

        for ($y = 0; $y < $h; $y++) {
            $row = [];
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($canvas, $x, $y);
                $v = ((($rgb >> 16) & 0xFF) * 0.299) + ((($rgb >> 8) & 0xFF) * 0.587) + (($rgb & 0xFF) * 0.114);
                $row[] = $v;
                $sum += $v;
                $sumSq += $v * $v;
            }
            $lum[] = $row;
        }

        imagedestroy($canvas);
        imagedestroy($source);

        $pixels = $w * $h;
        $mean = $sum / $pixels;
        $variance = max(0.0, ($sumSq / $pixels) - ($mean * $mean));
        $std = sqrt($variance);

        // Laplacian variance + gradien
        $lapSum = 0.0;
        $lapSumSq = 0.0;
        $lapCount = 0;
        $gradTotal = 0.0;
        $gradCount = 0;

        $borderGrad = 0.0;
        $borderCount = 0;
        $centreGrad = 0.0;
        $centreCount = 0;

        $bx = max(2, (int) round($w * 0.03));
        $by = max(2, (int) round($h * 0.03));
        $cx0 = (int) round($w * 0.2);
        $cx1 = max($cx0 + 1, (int) round($w * 0.8));
        $cy0 = (int) round($h * 0.2);
        $cy1 = max($cy0 + 1, (int) round($h * 0.8));

        for ($y = 1; $y < $h - 1; $y++) {
            for ($x = 1; $x < $w - 1; $x++) {
                $c = $lum[$y][$x];
                $lap = (-4 * $c) + $lum[$y - 1][$x] + $lum[$y + 1][$x] + $lum[$y][$x - 1] + $lum[$y][$x + 1];

                $lapSum += $lap;
                $lapSumSq += $lap * $lap;
                $lapCount++;

                $gx = $lum[$y][$x + 1] - $c;
                $gy = $lum[$y + 1][$x] - $c;
                $grad = sqrt(($gx * $gx) + ($gy * $gy));
                $gradTotal += $grad;
                $gradCount++;

                $isBorder = $x < $bx || $x >= $w - $bx || $y < $by || $y >= $h - $by;

                if ($isBorder) {
                    $borderGrad += $grad;
                    $borderCount++;
                } elseif ($x >= $cx0 && $x < $cx1 && $y >= $cy0 && $y < $cy1) {
                    $centreGrad += $grad;
                    $centreCount++;
                }
            }
        }

        $lapMean = $lapCount > 0 ? $lapSum / $lapCount : 0.0;
        $lapVar = $lapCount > 0 ? max(0.0, ($lapSumSq / $lapCount) - ($lapMean * $lapMean)) : 0.0;
        $tenengrad = $gradCount > 0 ? ($gradTotal / $gradCount) ** 2 : 0.0;

        // Metrik area tengah (25%-75%) — lebih mewakili ketajaman subjek
        $c0x = (int) round($w * 0.25);
        $c1x = (int) round($w * 0.75);
        $c0y = (int) round($h * 0.25);
        $c1y = (int) round($h * 0.75);

        $cenLapSum = 0.0;
        $cenLapSumSq = 0.0;
        $cenLapCount = 0;
        $cenSum = 0.0;
        $cenSumSq = 0.0;
        $cenPixels = 0;

        for ($y = max(1, $c0y); $y < min($h - 1, $c1y); $y++) {
            for ($x = max(1, $c0x); $x < min($w - 1, $c1x); $x++) {
                $c = $lum[$y][$x];
                $lap = (-4 * $c) + $lum[$y - 1][$x] + $lum[$y + 1][$x] + $lum[$y][$x - 1] + $lum[$y][$x + 1];
                $cenLapSum += $lap;
                $cenLapSumSq += $lap * $lap;
                $cenLapCount++;

                $cenSum += $c;
                $cenSumSq += $c * $c;
                $cenPixels++;
            }
        }

        $cenLapMean = $cenLapCount > 0 ? $cenLapSum / $cenLapCount : 0.0;
        $cenLapVar = $cenLapCount > 0 ? max(0.0, ($cenLapSumSq / $cenLapCount) - ($cenLapMean * $cenLapMean)) : 0.0;
        $cenMean = $cenPixels > 0 ? $cenSum / $cenPixels : 0.0;
        $cenStd = $cenPixels > 0 ? sqrt(max(0.0, ($cenSumSq / $cenPixels) - ($cenMean * $cenMean))) : 0.0;
        $normCenter = $cenLapVar / (max($cenStd, 1e-6) ** 2);

        $borderAvg = $borderCount > 0 ? $borderGrad / $borderCount : 0.0;
        $centreAvg = $centreCount > 0 ? $centreGrad / $centreCount : 0.0;
        $borderRatio = $centreAvg > 0 ? $borderAvg / $centreAvg : 0.0;

        // Normalisasi kontras lalu petakan ke 1-10 dengan kalibrasi batch
        $safeStd = max($std, 1e-6);
        $normSharp = $lapVar / ($safeStd ** 2);
        $score = $this->scaleScore($normCenter, self::CEN_P5, self::CEN_P95);
        $wholeScore = $this->scaleScore($normSharp, self::WHOLE_P5, self::WHOLE_P95);

        return [
            'orig_w' => $origW,
            'orig_h' => $origH,
            'analysis_w' => $w,
            'analysis_h' => $h,
            'laplacian' => round($lapVar, 1),
            'tenengrad' => round($tenengrad, 1),
            'norm_sharp' => round($normSharp, 3),
            'norm_center' => round($normCenter, 3),
            'centre_laplacian' => round($cenLapVar, 1),
            'classic_score' => $score,
            'classic_whole_score' => $wholeScore,
            'mean_lum' => round($mean, 1),
            'std' => round($std, 1),
            'border_ratio' => round($borderRatio, 3),
            'border_label' => $borderRatio >= 0.95 ? 'tinggi' : ($borderRatio >= 0.60 ? 'sedang' : 'rendah'),
            'is_dark' => $mean < 45,
            // Ambang absolut dari uji kontrol: 1872 tajam, 1406, 690, 288, 132, 39 (blur berat)
            'verdict' => $this->verdict($cenLapVar),
            'blur_flag' => $cenLapVar < 400,
        ];
    }

    /** Label ketajaman dari Laplacian variance area tengah (analisis 512 px). */
    protected function verdict(float $centreLaplacian): string
    {
        return match (true) {
            $centreLaplacian >= 1200 => 'tajam',
            $centreLaplacian >= 800 => 'cukup tajam',
            $centreLaplacian >= 400 => 'agak soft',
            $centreLaplacian >= 150 => 'blur ringan',
            default => 'blur',
        };
    }

    /**
     * Petakan nilai mentah ke skala 1-10 memakai kalibrasi log dari 265 foto.
     */
    protected function scaleScore(float $value, float $p5, float $p95): float
    {
        $logged = log(1 + max(0.0, $value));
        $span = $p95 - $p5;

        if ($span <= 0) {
            return 5.0;
        }

        $x = ($logged - $p5) / $span;

        return round(max(1.0, min(10.0, 1 + ($x * 9))), 1);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $gap = null;

        if ($this->ai !== null && $this->classic !== null) {
            $gap = round(abs(((float) $this->ai['rating']) - ((float) $this->classic['classic_score'])), 1);
        }

        return [
            'gap' => $gap,
        ];
    }
}
