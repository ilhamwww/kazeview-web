<?php

namespace App\Services;

use App\Models\Content;
use App\Models\PhotoMotorSearchIndex;
use App\Support\FloatVector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;

class MotorPhotoSearchService
{
    public function __construct(private NineRouterAiService $ai)
    {
    }

    /**
     * @return array{descriptor: array<string, mixed>, matches: Collection<int, array<string, mixed>>}
     */
    public function search(Content $content, UploadedFile $upload): array
    {
        if (! $content->is_active || ! $content->isPhotoContent()) {
            throw new RuntimeException('Content foto tidak tersedia.');
        }

        if (! $content->ai_photo_search_enabled) {
            throw new RuntimeException('AI Photo Search tidak aktif untuk Content ini.');
        }

        if (! $this->ai->configured()) {
            throw new RuntimeException('Provider AI belum dikonfigurasi.');
        }

        $temporaryPath = $this->makeThumbnail($upload->getRealPath());

        try {
            $descriptor = $this->ai->describeMotorcycle($temporaryPath, 'image/jpeg');
            $queryVector = FloatVector::normalize(
                $this->ai->embed($this->ai->retrievalDescriptor($descriptor)),
            );
        } finally {
            @unlink($temporaryPath);
        }

        $limit = max(1, min(50, (int) config('services.ninerouter.search_limit', 20)));
        $disk = Storage::disk('public');

        $matches = PhotoMotorSearchIndex::query()
            ->where('content_id', $content->getKey())
            ->whereIn('status', ['indexed', 'reindexing'])
            ->where('embedding_dimensions', count($queryVector))
            ->whereNotNull('embedding')
            ->with('photo.folder')
            ->get()
            ->map(function (PhotoMotorSearchIndex $index) use (
                $queryVector,
                $descriptor,
                $disk,
            ): ?array {
                $photo = $index->photo;

                if (! $photo || ! $disk->exists($photo->storagePath())) {
                    return null;
                }

                $binary = $index->getRawOriginal('embedding');

                if (is_resource($binary)) {
                    $binary = stream_get_contents($binary);
                }

                if (! is_string($binary) || $binary === '') {
                    return null;
                }

                try {
                    $score = FloatVector::dot(
                        $queryVector,
                        FloatVector::decode($binary),
                    );
                } catch (\InvalidArgumentException) {
                    return null;
                }

                $candidateDescriptor = is_array($index->descriptor)
                    ? $index->descriptor
                    : [];
                $identity = $this->identityEvidence(
                    $descriptor,
                    $candidateDescriptor,
                );
                $helmet = $this->helmetEvidence(
                    $descriptor,
                    $candidateDescriptor,
                );
                $rankingScore = $this->rankingScore(
                    $score,
                    $identity,
                    $helmet['score'],
                );

                return [
                    'id' => $photo->getKey(),
                    'name' => $photo->file_name,
                    'url' => $disk->url($photo->storagePath()),
                    'score' => round($rankingScore, 6),
                    'visual_score' => round($score, 6),
                    'motor_identity_score' => round($identity['score'], 6),
                    'identity_score' => round($identity['score'], 6),
                    'helmet_score' => round($helmet['score'], 6),
                    'evidence' => [
                        'motor_model' => $identity['model'],
                        'motor_brand' => $identity['brand'],
                        'helmet' => $helmet['evidence'],
                    ],
                    'candidate_analysis' => $candidateDescriptor,
                    'confidence' => $this->confidence(
                        $rankingScore,
                        $identity,
                    ),
                    'folder' => $photo->folder
                        ? [
                            'id' => $photo->folder->getKey(),
                            'name' => $photo->folder->name,
                            'relative_path' => $photo->folder->relative_path,
                        ]
                        : null,
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return [
            'descriptor' => $descriptor,
            'matches' => $matches,
        ];
    }

    private function makeThumbnail(string $sourcePath): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'kaze-search-');

        if ($temporaryPath === false) {
            throw new RuntimeException('File sementara pencarian tidak dapat dibuat.');
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($sourcePath);
        $image->scaleDown(width: 2048, height: 2048);

        file_put_contents(
            $temporaryPath,
            (string) $image->encode(new JpegEncoder(quality: 92)),
        );

        return $temporaryPath;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $candidate
     * @return array{score: float, model: string, brand: string}
     */
    private function identityEvidence(array $query, array $candidate): array
    {
        $queryModel = $this->specificModel($query['model_guess'] ?? null);
        $candidateModel = $this->specificModel(
            $candidate['model_guess'] ?? null,
        );
        $queryBrand = $this->token($query['brand_guess'] ?? null);
        $candidateBrand = $this->token(
            $candidate['brand_guess'] ?? null,
        );

        $modelEvidence = 'unknown';
        $score = 0.0;

        if ($queryModel !== null && $candidateModel !== null) {
            if ($queryModel === $candidateModel) {
                $modelEvidence = 'match';
                $score += 0.85;
            } elseif (
                $queryBrand !== null
                && $candidateBrand !== null
                && $queryBrand === $candidateBrand
            ) {
                // A model guess is probabilistic. Treat it as a hard conflict
                // only when both descriptors independently agree on brand.
                $modelEvidence = 'conflict';
                $score -= 0.65;
            }
        }

        $brandEvidence = 'unknown';

        if ($queryBrand !== null && $candidateBrand !== null) {
            if ($queryBrand === $candidateBrand) {
                $brandEvidence = 'match';
                $score += 0.18;
            } else {
                $brandEvidence = 'conflict';
                $score -= 0.35;
            }
        }

        $score += $this->equalFieldScore(
            $query,
            $candidate,
            'category',
            0.12,
        );
        $score += $this->equalFieldScore(
            $query,
            $candidate,
            'primary_color',
            0.08,
        );
        $score += $this->equalFieldScore(
            $query,
            $candidate,
            'wheel_color',
            0.10,
        );
        $score += $this->tokenOverlapScore(
            $query['fairing'] ?? null,
            $candidate['fairing'] ?? null,
            0.08,
        );
        $score += $this->arrayOverlapScore(
            $query['secondary_colors'] ?? [],
            $candidate['secondary_colors'] ?? [],
            0.06,
        );
        $score += $this->tokenOverlapScore(
            $query['windshield'] ?? null,
            $candidate['windshield'] ?? null,
            0.05,
        );
        $score += $this->arrayOverlapScore(
            $query['decals'] ?? [],
            $candidate['decals'] ?? [],
            0.14,
        );
        $score += $this->arrayOverlapScore(
            $query['visible_text'] ?? [],
            $candidate['visible_text'] ?? [],
            0.18,
        );
        $score += $this->arrayOverlapScore(
            $query['accessories'] ?? [],
            $candidate['accessories'] ?? [],
            0.05,
        );
        $score += $this->arrayOverlapScore(
            $query['distinctive_features'] ?? [],
            $candidate['distinctive_features'] ?? [],
            0.16,
        );

        return [
            'score' => max(-1.0, min(1.5, $score)),
            'model' => $modelEvidence,
            'brand' => $brandEvidence,
        ];
    }

    /**
     * Keep semantic similarity for broad recall, then rerank primarily using
     * motorcycle identity. Helmet similarity is secondary; rider clothing,
     * watermark, and background never add an explicit identity bonus.
     *
     * @param  array{score: float, model: string, brand: string}  $identity
     */
    private function rankingScore(
        float $visualScore,
        array $identity,
        float $helmetScore = 0.0,
    ): float {
        // Motorcycle identity remains dominant. Helmet evidence contributes
        // at most 0.06 to the final rank and cannot cancel a model conflict.
        $helmetBonus = $identity['model'] === 'conflict'
            || $identity['brand'] === 'conflict'
                ? 0.0
                : max(0.0, min(1.0, $helmetScore)) * 0.06;

        return $visualScore
            + ($identity['score'] * 0.20)
            + $helmetBonus;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $candidate
     * @return array{score: float, evidence: string}
     */
    private function helmetEvidence(array $query, array $candidate): array
    {
        $queryHelmet = $this->helmetDescriptor($query);
        $candidateHelmet = $this->helmetDescriptor($candidate);

        if ($queryHelmet === [] || $candidateHelmet === []) {
            return ['score' => 0.0, 'evidence' => 'unknown'];
        }

        $score = 0.0;
        $score += $this->equalValueScore(
            $queryHelmet['type'] ?? null,
            $candidateHelmet['type'] ?? null,
            0.12,
        );
        $score += $this->equalValueScore(
            $queryHelmet['primary_color'] ?? null,
            $candidateHelmet['primary_color'] ?? null,
            0.18,
        );
        $score += $this->equalValueScore(
            $queryHelmet['brand_guess'] ?? null,
            $candidateHelmet['brand_guess'] ?? null,
            0.16,
        );
        $score += $this->tokenOverlapScore(
            $queryHelmet['visor'] ?? null,
            $candidateHelmet['visor'] ?? null,
            0.12,
        );
        $score += $this->arrayOverlapScore(
            $queryHelmet['secondary_colors'] ?? [],
            $candidateHelmet['secondary_colors'] ?? [],
            0.10,
        );
        $score += $this->arrayOverlapScore(
            $queryHelmet['graphics'] ?? [],
            $candidateHelmet['graphics'] ?? [],
            0.16,
        );
        $score += $this->arrayOverlapScore(
            $queryHelmet['visible_text'] ?? [],
            $candidateHelmet['visible_text'] ?? [],
            0.08,
        );
        $score += $this->arrayOverlapScore(
            $queryHelmet['distinctive_features'] ?? [],
            $candidateHelmet['distinctive_features'] ?? [],
            0.08,
        );

        // Existing motor-v1 indexes only have rider_helmet. Keep them useful
        // until a future reindex supplies the structured helmet object.
        $score += $this->tokenOverlapScore(
            $queryHelmet['legacy_summary'] ?? null,
            $candidateHelmet['legacy_summary'] ?? null,
            0.30,
        );

        $score = max(0.0, min(1.0, $score));

        return [
            'score' => $score,
            'evidence' => $score >= 0.65
                ? 'strong'
                : ($score >= 0.30 ? 'partial' : 'weak'),
        ];
    }

    /**
     * @param  array<string, mixed>  $descriptor
     * @return array<string, mixed>
     */
    private function helmetDescriptor(array $descriptor): array
    {
        $helmet = is_array($descriptor['helmet'] ?? null)
            ? $descriptor['helmet']
            : [];

        if (($helmet['present'] ?? true) === false) {
            return [];
        }

        $legacy = $descriptor['rider_helmet'] ?? null;

        if ($helmet === [] && $this->token($legacy) === null) {
            return [];
        }

        $helmet['legacy_summary'] = $legacy;

        return $helmet;
    }

    private function equalValueScore(
        mixed $left,
        mixed $right,
        float $weight,
    ): float {
        $left = $this->token($left);
        $right = $this->token($right);

        return $left !== null && $left === $right ? $weight : 0.0;
    }

    /**
     * @param  array{score: float, model: string, brand: string}  $identity
     */
    private function confidence(float $score, array $identity): string
    {
        if (
            $identity['model'] === 'conflict'
            || $identity['brand'] === 'conflict'
        ) {
            return 'low';
        }

        if ($identity['model'] === 'match') {
            // The identity bonus raises the combined score, so "high" needs
            // stronger visual agreement than merely matching a model guess.
            return $score >= 1.15 ? 'high' : 'medium';
        }

        // A generic descriptor such as just "ninja" is not enough evidence
        // for medium confidence, even when its overall visual scene is close.
        return 'low';
    }

    private function equalFieldScore(
        array $query,
        array $candidate,
        string $field,
        float $weight,
    ): float {
        $left = $this->token($query[$field] ?? null);
        $right = $this->token($candidate[$field] ?? null);

        return $left !== null && $left === $right ? $weight : 0.0;
    }

    private function tokenOverlapScore(
        mixed $left,
        mixed $right,
        float $weight,
    ): float {
        $leftTokens = $this->words($left);
        $rightTokens = $this->words($right);

        if ($leftTokens === [] || $rightTokens === []) {
            return 0.0;
        }

        $intersection = array_intersect($leftTokens, $rightTokens);
        $union = array_unique([...$leftTokens, ...$rightTokens]);

        return $weight * (count($intersection) / count($union));
    }

    private function arrayOverlapScore(
        mixed $left,
        mixed $right,
        float $weight,
    ): float {
        if (! is_array($left) || ! is_array($right)) {
            return 0.0;
        }

        return $this->tokenOverlapScore(
            implode(' ', $left),
            implode(' ', $right),
            $weight,
        );
    }

    /**
     * Return a format-independent specific model identifier. Generic families
     * are ignored because they cannot reliably distinguish variants.
     */
    private function specificModel(mixed $value): ?string
    {
        $token = $this->token($value);

        if ($token === null) {
            return null;
        }

        $compact = preg_replace('/[^a-z0-9]+/', '', $token) ?? '';

        $generic = [
            'motorcycle',
            'motorbike',
            'bike',
            'sport',
            'sportbike',
            'supersport',
            'ninja',
            'kawasakininja',
            'panigale',
            'ducatipanigale',
            'cbr',
            'hondacbr',
            'yzf',
            'yamahayzf',
            'rseries',
            'gsxr',
            'suzukigsxr',
            'rr',
        ];

        if ($compact === '' || in_array($compact, $generic, true)) {
            return null;
        }

        // Remove a duplicated brand/family prefix while preserving every
        // distinguishing number and suffix (for example V4S, R15M, 250RR).
        $compact = preg_replace(
            '/^(kawasaki|honda|yamaha|suzuki|ducati|bmw|ktm|aprilia|triumph|harleydavidson)+/',
            '',
            $compact,
        ) ?? $compact;
        $compact = preg_replace('/^(ninja|panigale|yzf)+/', '', $compact) ?? $compact;

        return $compact === '' || in_array($compact, $generic, true)
            ? null
            : $compact;
    }

    private function token(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value === '' || $value === 'unknown' ? null : $value;
    }

    /**
     * @return array<int, string>
     */
    private function words(mixed $value): array
    {
        $token = $this->token($value);

        if ($token === null) {
            return [];
        }

        $ignored = [
            'a', 'an', 'and', 'with', 'the', 'of', 'on', 'in',
            'front', 'side', 'full', 'small', 'large',
        ];

        return array_values(array_unique(array_filter(
            explode(' ', $token),
            fn (string $word): bool => mb_strlen($word) > 1
                && ! in_array($word, $ignored, true),
        )));
    }
}