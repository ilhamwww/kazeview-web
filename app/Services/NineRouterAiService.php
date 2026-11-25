<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class NineRouterAiService
{
    /**
     * @return array<string, mixed>
     */
    public function describeMotorcycle(string $imagePath, ?string $mimeType = null): array
    {
        if (! is_file($imagePath) || ! is_readable($imagePath)) {
            throw new RuntimeException('File gambar tidak dapat dibaca.');
        }

        $mimeType ??= mime_content_type($imagePath) ?: 'image/jpeg';
        $dataUrl = sprintf(
            'data:%s;base64,%s',
            $mimeType,
            base64_encode((string) file_get_contents($imagePath)),
        );

        $response = $this->request()
            ->post($this->url('/chat/completions'), [
                'model' => $this->visionModel(),
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->descriptorPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Analisis foto ini menggunakan schema JSON yang diminta.',
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => ['url' => $dataUrl],
                            ],
                        ],
                    ],
                ],
            ])
            ->throw();

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Provider tidak mengembalikan descriptor gambar.');
        }

        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content)) ?? $content;

        try {
            $descriptor = json_decode($content, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Descriptor gambar bukan JSON yang valid.', 0, $exception);
        }

        if (! is_array($descriptor)) {
            throw new RuntimeException('Descriptor gambar memiliki format yang tidak valid.');
        }

        return $descriptor;
    }

    /**
     * @param  array<string, mixed>  $descriptor
     */
    public function canonicalDescriptor(array $descriptor): string
    {
        $normalized = $this->sortRecursively($descriptor);

        return json_encode(
            $normalized,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * Build a stable retrieval document from the existing descriptor schema.
     * The returned JSON descriptor itself is not changed; this representation
     * only prevents rider/background details from dominating the embedding.
     *
     * @param  array<string, mixed>  $descriptor
     */
    public function retrievalDescriptor(array $descriptor): string
    {
        $helmet = is_array($descriptor['helmet'] ?? null)
            ? $descriptor['helmet']
            : [];

        $parts = [
            'motorcycle brand: '.$this->retrievalValue($descriptor['brand_guess'] ?? null),
            'motorcycle model: '.$this->retrievalValue($descriptor['model_guess'] ?? null),
            'motorcycle category: '.$this->retrievalValue($descriptor['category'] ?? null),
            'motorcycle primary color: '.$this->retrievalValue($descriptor['primary_color'] ?? null),
            'motorcycle license plate: '.$this->retrievalValue($descriptor['license_plate'] ?? null),
            'motorcycle secondary colors: '.$this->retrievalValue($descriptor['secondary_colors'] ?? null),
            'motorcycle fairing: '.$this->retrievalValue($descriptor['fairing'] ?? null),
            'motorcycle windshield: '.$this->retrievalValue($descriptor['windshield'] ?? null),
            'motorcycle wheel color: '.$this->retrievalValue($descriptor['wheel_color'] ?? null),
            'motorcycle decals: '.$this->retrievalValue($descriptor['decals'] ?? null),
            'motorcycle accessories: '.$this->retrievalValue($descriptor['accessories'] ?? null),
            'motorcycle visible text: '.$this->retrievalValue($descriptor['visible_text'] ?? null),
            'motorcycle distinctive features: '.$this->retrievalValue($descriptor['distinctive_features'] ?? null),
            'helmet type: '.$this->retrievalValue($helmet['type'] ?? null),
            'helmet colors: '.$this->retrievalValue([
                $helmet['primary_color'] ?? null,
                ...($helmet['secondary_colors'] ?? []),
            ]),
            'helmet graphics: '.$this->retrievalValue($helmet['graphics'] ?? null),
        ];

        return implode("\n", $parts);
    }

    /**
     * @return array<int, float>
     */
    public function embed(string $input): array
    {
        $response = $this->request()
            ->post($this->url('/embeddings'), [
                'model' => $this->embeddingModel(),
                'input' => $input,
            ])
            ->throw();

        $embedding = $response->json('data.0.embedding');

        if (! is_array($embedding) || $embedding === []) {
            throw new RuntimeException('Provider tidak mengembalikan embedding.');
        }

        foreach ($embedding as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw new RuntimeException('Provider mengembalikan embedding yang tidak valid.');
            }
        }

        return array_map(static fn (int|float $value): float => (float) $value, $embedding);
    }

    public function configured(): bool
    {
        return filled(config('services.ninerouter.key'));
    }

    public function visionModel(): string
    {
        return (string) config('services.ninerouter.vision_model');
    }

    public function embeddingModel(): string
    {
        return (string) config('services.ninerouter.embedding_model');
    }

    public function promptVersion(): string
    {
        return (string) config('services.ninerouter.prompt_version');
    }

    /**
     * Nilai kualitas foto (fokus & kelengkapan objek) tanpa mengubah descriptor.
     * Dipakai untuk memberi rating foto yang sudah terlanjur terindeks.
     *
     * @return array{rating:int,focus:?int,framing:?int,subject_cut:?bool,note:?string}
     */
    public function assessQuality(string $imagePath, ?string $mimeType = null): array
    {
        if (! is_file($imagePath) || ! is_readable($imagePath)) {
            throw new RuntimeException('File gambar tidak dapat dibaca.');
        }

        $mimeType ??= mime_content_type($imagePath) ?: 'image/jpeg';
        $dataUrl = sprintf(
            'data:%s;base64,%s',
            $mimeType,
            base64_encode((string) file_get_contents($imagePath)),
        );

        $response = $this->request()
            ->post($this->url('/chat/completions'), [
                'model' => $this->visionModel(),
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->qualityPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Nilai kualitas foto ini menggunakan schema JSON yang diminta.',
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => ['url' => $dataUrl],
                            ],
                        ],
                    ],
                ],
            ])
            ->throw();

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Provider tidak mengembalikan nilai kualitas foto.');
        }

        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content)) ?? $content;

        try {
            $decoded = json_decode($content, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Nilai kualitas bukan JSON yang valid.', 0, $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Nilai kualitas memiliki format yang tidak valid.');
        }

        return $this->normalizeQuality($decoded);
    }

    /**
     * Ambil blok quality dari descriptor hasil indexing.
     *
     * @param  array<string, mixed>  $descriptor
     * @return array{rating:int,focus:?int,framing:?int,subject_cut:?bool,note:?string}|null
     */
    public function qualityFromDescriptor(array $descriptor): ?array
    {
        $quality = $descriptor['quality'] ?? null;

        if (! is_array($quality)) {
            return null;
        }

        return $this->normalizeQuality($quality);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{rating:int,focus:?int,framing:?int,subject_cut:?bool,note:?string}
     */
    private function normalizeQuality(array $raw): array
    {
        $toScore = function (mixed $value): ?int {
            if (! is_numeric($value)) {
                return null;
            }

            return max(1, min(10, (int) round((float) $value)));
        };

        $focus = $toScore($raw['focus_score'] ?? null);
        $framing = $toScore($raw['framing_score'] ?? null);
        $rating = $toScore($raw['rating'] ?? null);

        if ($rating === null) {
            $fallback = array_values(array_filter([$focus, $framing], fn ($v) => $v !== null));
            $rating = $fallback === []
                ? 1
                : max(1, min(10, (int) round(array_sum($fallback) / count($fallback))));
        }

        $subjectCut = $raw['subject_cut'] ?? null;

        // Versi prompt baru memakai tiga tingkat; subject_cut hanya true bila mayor.
        $severity = strtolower(trim((string) ($raw['crop_severity'] ?? '')));
        $severity = in_array($severity, ['none', 'minor', 'major'], true) ? $severity : null;

        if ($severity !== null) {
            $subjectCut = $severity === 'major';
        } elseif (is_bool($subjectCut)) {
            $severity = $subjectCut ? 'major' : 'none';
        } else {
            $subjectCut = null;
        }

        $note = $raw['rating_note'] ?? null;

        if (! is_string($note) || trim($note) === '') {
            $note = $raw['focus_note'] ?? $raw['framing_note'] ?? null;
        }

        return [
            'rating' => $rating,
            'focus' => $focus,
            'framing' => $framing,
            'subject_cut' => is_bool($subjectCut) ? $subjectCut : null,
            'crop_severity' => $severity,
            'note' => is_string($note) ? mb_substr(trim($note), 0, 500) : null,
        ];
    }

    private function qualityPrompt(): string
    {
        return <<<'PROMPT'
You rate the photographic quality of motorcycle event photos for editorial sorting. Return JSON only with this exact shape:
{
  "rating": 8,
  "focus_score": 9,
  "focus_note": "string",
  "framing_score": 7,
  "crop_severity": "none",
  "framing_note": "string",
  "rating_note": "string"
}
Use whole numbers 1 to 10.

focus_score measures how sharp and correctly focused the motorcycle is: 10 means tack sharp on the machine, lower values mean soft focus, motion blur, or focus sitting on the background/foreground instead of the motorcycle.

framing_score measures how completely the motorcycle sits inside the frame: 10 means the entire machine, both wheels included, is visible with comfortable margin around it, lower values mean parts of the machine reach or cross the border or the machine sits awkwardly against an edge. Do not lower framing_score merely because the background has empty sky, road or space; judge the motorcycle, not the surroundings.

crop_severity describes how much of the motorcycle body falls outside the photo edges, using exactly one of: "none", "minor", "major".
- "none": the entire motorcycle, including both wheels, is inside the frame.
- "minor": only small peripheral parts are clipped by the edge, for example the very bottom of a tyre touching the border, a mirror tip, an exhaust tip, a pillion seat edge, or the rider's foot. The motorcycle is still fully recognisable and its body complete.
- "major": a significant body component is fully or largely outside the frame, for example a complete wheel missing, the front cowl or tail cut away, roughly a third or more of the motorcycle missing, or the machine running off the frame edge.
Judge only the motorcycle itself. Do not treat occlusion by the rider, a puddle reflection, road markings, background objects like flags, poles or buildings, or a forearm crossing the machine as cropping.

rating is the overall usability as a motorcycle event photo. Derive it with this rubric instead of guessing: start from focus_score; subtract 1 if framing_score is below 6; subtract 2 more if crop_severity is "major"; subtract 1 if the motorcycle is very small in frame or heavily obstructed. Never subtract for empty sky, road, background clutter, or the rider's pose. Clamp the result between 1 and 10. Ignore watermarks, logos, timestamps, borders, and captions when judging quality. If no motorcycle is visible, set rating to 1 and explain. Keep every note short and lowercase.
PROMPT;
    }

    private function request(): PendingRequest
    {
        $key = config('services.ninerouter.key');

        if (! is_string($key) || trim($key) === '') {
            throw new RuntimeException('NINEROUTER_API_KEY belum dikonfigurasi.');
        }

        return Http::withToken($key)
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('services.ninerouter.connect_timeout', 10))
            ->timeout((int) config('services.ninerouter.timeout', 60))
            ->retry(2, 1000, throw: false);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.ninerouter.base_url'), '/').$path;
    }

    private function descriptorPrompt(): string
    {
        return <<<'PROMPT'
You analyze event photographs for high-precision motorcycle retrieval. Return JSON only, with concise factual visual attributes. Do not identify a person and do not infer sensitive traits. Use this exact object shape:
{
  "motorcycle_present": true,
  "motorcycle_count": 1,
  "category": "sport|naked|scooter|cruiser|adventure|underbone|trail|unknown",
  "primary_color": "string",
  "secondary_colors": ["string"],
  "brand_guess": "string or unknown",
  "model_guess": "string or unknown",
  "license_plate": "string or unknown",
  "helmets": [
    {
      "box_2d": [100, 200, 300, 400],
      "type": "full-face|modular|open-face|half-face|off-road|unknown",
      "primary_color": "string or unknown",
      "brand_guess": "string or unknown"
    }
  ],
  "fairing": "string",
  "windshield": "string",
  "wheel_color": "string",
  "decals": ["string"],
  "accessories": ["string"],
  "visible_text": ["string"],
  "helmet": {
    "present": true,
    "type": "full-face|modular|open-face|half-face|off-road|unknown",
    "primary_color": "string or unknown",
    "secondary_colors": ["string"],
    "graphics": ["string"],
    "visor": "string",
    "brand_guess": "string or unknown",
    "visible_text": ["string"],
    "distinctive_features": ["string"]
  },
  "rider_helmet": "short legacy summary of the helmet",
  "rider_clothing": "string",
  "distinctive_features": ["string"],
  "quality": {
    "rating": 8,
    "focus_score": 9,
    "focus_note": "string",
    "framing_score": 7,
    "crop_severity": "none|minor|major",
    "framing_note": "string",
    "rating_note": "string"
  }
}
The motorcycle is the primary retrieval subject. Ignore watermarks, photographer logos, filenames, frame counters, timestamps, captions, borders, and other scene overlays completely: never use them for identification and never copy them into visible_text. Inspect the motorcycle itself before the rider or scene.

Perform a model-independent visual inventory before choosing a model. First inspect actual text-bearing regions on the motorcycle: badges, emblems, tank or fairing text, decals, and model markings. For a possible model marking, verify each distinguishing digit and suffix independently. Never autocomplete blurred or partially hidden text from an expected decal or from a model you are considering. If only a generic fragment such as "zx" is clearly readable, record only that fragment.

Carefully inspect registration plates (Indonesian license plates: 1-2 letters, 1-4 digits, 1-3 letters, e.g. "b 1234 xyz", "ab 5732 sk") or competition race number decals on the windscreen, cowl, or fairing. If legible, record the number/plate in license_plate and also include it in visible_text.

Then inspect physical geometry: frame construction, headlight and intake layout, fairing vents, engine and body proportions, exhaust type (underbelly, side slip-on, full-system, or stock), front suspension (upside-down gold/black or telescopic), swingarm, wheels (alloy multi-spoke or wire spoke), windshield, and tail.

For popular Indonesian event motorcycles (e.g., Scooter: NMAX, Aerox, Vario, Beat, PCX, Scoopy, Vespa; Sport: Ninja 250, ZX-25R, ZX-6R, R15, R25, CBR150R, CBR250RR; Underbone: MX King, Satria F150, Sonic, Supra; Naked: MT-15, MT-25, CB150R, KLX, CRF150L), observe distinctive headlights, fairing shapes, and radiator/air intakes before concluding.

Report only components actually visible. Never infer the total number of brake discs, calipers, exhaust outlets, or other left/right components from a one-sided or occluded view. Do not mistake shadows, fairing gaps, panel seams, hoses, frame sliders, decorative graphics, or aftermarket accessories for structural frame members.

For model_guess, silently compare the closest visually similar models that are compatible with the detected brand. Treat generic family text, brand color, racing-style graphics, rider, helmet, background, watermark, mirrors, and other aftermarket parts as non-discriminating. Return a concise canonical model name only when at least two independent reliable cues support it, such as an exact fully legible model marking plus compatible geometry, or multiple compatible structural cues. Reject a candidate when clearly visible chassis or geometry contradicts it. A brand logo alone is not evidence for a specific model. Do not invent trim, engine size, generation, or suffix. If multiple models share the visible design or reliable evidence is insufficient, use "unknown". Ensure model_guess is compatible with brand_guess; otherwise use "unknown" for the uncertain field. Determine category from visible vehicle geometry rather than from the guessed model name.

Assess photographic quality for editorial sorting, in whole numbers from 1 to 10. focus_score measures how sharp and correctly focused the motorcycle is: 10 means tack sharp on the machine, lower values mean soft focus, motion blur, or focus sitting on the background or foreground instead of the motorcycle. framing_score measures how completely the motorcycle sits inside the frame: 10 means the entire machine, both wheels included, is visible with comfortable margin around it, lower values mean parts of the machine reach or cross the border or the machine sits awkwardly against an edge; do not lower it merely because the background has empty sky or road, since only the motorcycle is judged. crop_severity describes how much of the motorcycle falls outside the photo edges: "none" when the entire machine including both wheels is inside the frame; "minor" when only small peripheral parts are clipped, such as the very bottom of a tyre touching the border, a mirror tip, an exhaust tip, or the rider's foot, while the machine body is still complete and fully recognisable; "major" when a significant body component is largely or fully outside the frame, such as a complete wheel missing, the front cowl or tail cut away, roughly a third or more of the machine missing, or the machine running off the edge. Judge only the motorcycle itself and never treat occlusion by the rider, reflections, road markings, background objects, or a forearm crossing the machine as cropping. rating is the overall usability of this photo as a motorcycle event photo. Derive it with this rubric instead of guessing: start from focus_score, subtract 1 if framing_score is below 6, subtract 2 more if crop_severity is "major", and subtract 1 if the machine is very small in frame or heavily obstructed. Never subtract for empty sky, road, background clutter, or the rider's pose. Clamp the result between 1 and 10. Put the short reason in rating_note, and keep focus_note and framing_note factual. If no motorcycle is visible at all, set rating to 1 and explain in rating_note.

Analyze the helmet separately as secondary matching evidence: its colors, graphics, visor, visible text, and distinctive pattern can help distinguish riders, but never use helmet similarity to override a clearly conflicting motorcycle brand or reliable model. For every visible rider and passenger wearing a helmet, accurately detect their helmet bounding box in normalized coordinates 0 to 1000 and return in helmets: [{"box_2d": [ymin, xmin, ymax, xmax], "type": "...", "primary_color": "...", "brand_guess": "..."}]. If there is no motorcycle or helmet, set the corresponding present field false and keep uncertain fields as unknown or empty arrays. Do not identify a person. Describe visible evidence only. Keep every string short and lowercase.
PROMPT;
    }

    private function retrievalValue(mixed $value): string
    {
        $values = is_array($value) ? $value : [$value];
        $normalized = [];

        foreach ($values as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $item = mb_strtolower(trim((string) $item));

            if ($item !== '' && $item !== 'unknown' && ! in_array($item, $normalized, true)) {
                $normalized[] = $item;
            }
        }

        return $normalized === [] ? 'unknown' : implode(', ', $normalized);
    }

    /**
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private function sortRecursively(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => is_array($item) ? $this->sortRecursively($item) : $item,
                $value,
            );
        }

        ksort($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursively($item);
            }
        }

        return $value;
    }
}