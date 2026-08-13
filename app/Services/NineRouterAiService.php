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
You analyze event photographs for motorcycle retrieval. Return JSON only, with concise factual visual attributes. Do not identify a person and do not infer sensitive traits. Use this exact object shape:
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
  "rider_helmet": "string",
  "rider_clothing": "string",
  "distinctive_features": ["string"]
}
If there is no motorcycle, set motorcycle_present false and keep uncertain fields as unknown or empty arrays. Describe visible evidence only. Keep every string short and lowercase.
PROMPT;
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