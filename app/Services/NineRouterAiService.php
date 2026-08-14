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
  "distinctive_features": ["string"]
}
The motorcycle is the primary retrieval subject. Inspect the motorcycle itself before the rider or scene. First read visible badges, emblems, tank or fairing text, decals, and model markings; include every legible marking in visible_text. Then inspect the headlight, fairing geometry, intake, exhaust, swingarm, wheels, windshield, and body proportions. Use those visible cues together for brand_guess and model_guess.

For model_guess, return a concise canonical model name only when the photo contains sufficient distinguishing evidence. A brand logo alone is not evidence for a specific model. Never infer a model from color, rider, helmet, background, event context, or category alone. Do not invent trim, engine size, generation, or suffix. If multiple models share the visible design or the evidence is weak, use "unknown". Ensure model_guess is compatible with brand_guess; otherwise use "unknown" for the uncertain field. Determine category from visible vehicle geometry rather than from the guessed model name.

Analyze the helmet separately as secondary matching evidence: its colors, graphics, visor, visible text, and distinctive pattern can help distinguish riders, but never use helmet similarity to override a clearly conflicting motorcycle brand or reliable model. If there is no motorcycle or helmet, set the corresponding present field false and keep uncertain fields as unknown or empty arrays. Do not identify a person. Describe visible evidence only. Keep every string short and lowercase.
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