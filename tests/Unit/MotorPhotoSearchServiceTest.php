<?php

namespace Tests\Unit;

use App\Services\MotorPhotoSearchService;
use ReflectionMethod;
use Tests\TestCase;

class MotorPhotoSearchServiceTest extends TestCase
{
    public function test_specific_model_match_outranks_conflict_and_generic_candidate(): void
    {
        $service = app(MotorPhotoSearchService::class);
        $evidence = new ReflectionMethod($service, 'identityEvidence');
        $ranking = new ReflectionMethod($service, 'rankingScore');
        $confidence = new ReflectionMethod($service, 'confidence');

        $query = [
            'category' => 'sport',
            'primary_color' => 'green',
            'brand_guess' => 'kawasaki',
            'model_guess' => 'ninja zx-6r',
            'fairing' => 'full fairing',
            'wheel_color' => 'red',
            'decals' => ['kawasaki', 'ninja'],
            'distinctive_features' => ['red painted wheels', 'dual headlights'],
        ];

        $zx6r = [
            'category' => 'sport',
            'primary_color' => 'green',
            'brand_guess' => 'kawasaki',
            'model_guess' => 'ninja zx-6r',
            'fairing' => 'full fairing',
            'wheel_color' => 'red',
            'decals' => ['kawasaki', 'ninja'],
            'distinctive_features' => ['red painted wheels'],
        ];

        $zx25r = [
            'category' => 'sport',
            'primary_color' => 'green',
            'brand_guess' => 'kawasaki',
            'model_guess' => 'ninja zx-25r',
            'fairing' => 'full front sport fairing',
            'wheel_color' => 'red',
            'decals' => [],
            'distinctive_features' => ['dual headlights'],
        ];

        $genericNinja = [
            'category' => 'sport',
            'primary_color' => 'green',
            'brand_guess' => 'kawasaki',
            'model_guess' => 'ninja',
            'fairing' => 'full fairing',
            'wheel_color' => 'black',
            'decals' => ['ninja lettering'],
            'distinctive_features' => ['dual led headlights'],
        ];

        $zx6rEvidence = $evidence->invoke($service, $query, $zx6r);
        $zx25rEvidence = $evidence->invoke($service, $query, $zx25r);
        $genericEvidence = $evidence->invoke($service, $query, $genericNinja);

        $visualScore = 0.78;
        $zx6rScore = $ranking->invoke($service, $visualScore, $zx6rEvidence);
        $zx25rScore = $ranking->invoke($service, $visualScore, $zx25rEvidence);
        $genericScore = $ranking->invoke($service, $visualScore, $genericEvidence);

        $this->assertSame('match', $zx6rEvidence['model']);
        $this->assertSame('conflict', $zx25rEvidence['model']);
        $this->assertSame('unknown', $genericEvidence['model']);
        $this->assertGreaterThan($genericScore, $zx6rScore);
        $this->assertGreaterThan($zx25rScore, $genericScore);
        $this->assertSame(
            'medium',
            $confidence->invoke($service, $zx6rScore, $zx6rEvidence),
        );
        $this->assertSame(
            'low',
            $confidence->invoke($service, $zx25rScore, $zx25rEvidence),
        );
        $this->assertSame(
            'low',
            $confidence->invoke($service, $genericScore, $genericEvidence),
        );
    }

    public function test_model_format_variations_are_normalized_without_changing_descriptor(): void
    {
        $service = app(MotorPhotoSearchService::class);
        $specificModel = new ReflectionMethod($service, 'specificModel');

        $this->assertSame(
            'cbr250rr',
            $specificModel->invoke($service, 'Honda CBR 250 RR'),
        );
        $this->assertSame(
            'cbr250rr',
            $specificModel->invoke($service, 'cbr-250rr'),
        );
        $this->assertSame(
            'v4s',
            $specificModel->invoke($service, 'Ducati Panigale V4 S'),
        );
        $this->assertSame(
            'v4s',
            $specificModel->invoke($service, 'panigale v4s'),
        );
        $this->assertNull($specificModel->invoke($service, 'Ducati Panigale'));
        $this->assertNull($specificModel->invoke($service, 'Kawasaki Ninja'));
    }

    public function test_model_difference_is_not_a_hard_conflict_when_brand_is_not_reliable(): void
    {
        $service = app(MotorPhotoSearchService::class);
        $evidence = new ReflectionMethod($service, 'identityEvidence');

        $query = [
            'brand_guess' => 'ducati',
            'model_guess' => 'panigale v4',
            'visible_text' => ['ducati'],
        ];
        $candidate = [
            'brand_guess' => 'unknown',
            'model_guess' => 'kawasaki zx-6r',
            'visible_text' => ['ducati'],
        ];

        $result = $evidence->invoke($service, $query, $candidate);

        $this->assertSame('unknown', $result['model']);
        $this->assertSame('unknown', $result['brand']);
        $this->assertGreaterThan(0.0, $result['score']);
    }

    public function test_gallery_brand_filter_canonicalizes_ai_descriptors_and_unknown(): void
    {
        $controller = app(\App\Http\Controllers\Api\PublicApiController::class);
        $canonicalBrand = new ReflectionMethod(
            $controller,
            'canonicalMotorcycleBrand',
        );

        $indexed = static fn (
            mixed $brand,
            bool $motorcyclePresent = true,
        ): object => (object) [
            'status' => 'indexed',
            'descriptor' => [
                'motorcycle_present' => $motorcyclePresent,
                'brand_guess' => $brand,
            ],
        ];

        $this->assertSame(
            'kawasaki',
            $canonicalBrand->invoke(
                $controller,
                $indexed('Kawasaki Motorcycles'),
            ),
        );
        $this->assertSame(
            'kawasaki',
            $canonicalBrand->invoke(
                $controller,
                $indexed('Kawasaki Ninja'),
            ),
        );
        $this->assertSame(
            'harley-davidson',
            $canonicalBrand->invoke(
                $controller,
                $indexed('Harley-Davidson'),
            ),
        );
        $this->assertSame(
            'bmw',
            $canonicalBrand->invoke(
                $controller,
                $indexed('BMW Motorrad'),
            ),
        );
        $this->assertSame(
            'unknown',
            $canonicalBrand->invoke($controller, $indexed('unknown')),
        );
        $this->assertSame(
            'unknown',
            $canonicalBrand->invoke(
                $controller,
                $indexed('Honda', false),
            ),
        );
        $this->assertSame(
            'unknown',
            $canonicalBrand->invoke(
                $controller,
                (object) [
                    'status' => 'processing',
                    'descriptor' => ['brand_guess' => 'yamaha'],
                ],
            ),
        );
        $this->assertSame(
            'unknown',
            $canonicalBrand->invoke($controller, null),
        );
    }

    public function test_gallery_category_filter_canonicalizes_ai_descriptors_and_unknown(): void
    {
        $controller = app(\App\Http\Controllers\HomeController::class);
        $canonicalCategory = new ReflectionMethod(
            $controller,
            'canonicalMotorcycleCategory',
        );

        $indexed = static fn (
            mixed $category,
            bool $motorcyclePresent = true,
        ): object => (object) [
            'status' => 'indexed',
            'descriptor' => [
                'motorcycle_present' => $motorcyclePresent,
                'category' => $category,
            ],
        ];

        $this->assertSame(
            'sport',
            $canonicalCategory->invoke($controller, $indexed('sport')),
        );
        $this->assertSame(
            'sport',
            $canonicalCategory->invoke($controller, $indexed('Sport Bike')),
        );
        $this->assertSame(
            'adventure',
            $canonicalCategory->invoke(
                $controller,
                $indexed('Adventure Touring'),
            ),
        );
        $this->assertSame(
            'dual-sport',
            $canonicalCategory->invoke($controller, $indexed('Dual Sport')),
        );
        $this->assertSame(
            'cafe-racer',
            $canonicalCategory->invoke($controller, $indexed('Cafe Racer')),
        );
        $this->assertSame(
            'unknown',
            $canonicalCategory->invoke($controller, $indexed('unknown')),
        );
        $this->assertSame(
            'unknown',
            $canonicalCategory->invoke(
                $controller,
                $indexed('sport', false),
            ),
        );
        $this->assertSame(
            'unknown',
            $canonicalCategory->invoke(
                $controller,
                (object) [
                    'status' => 'processing',
                    'descriptor' => ['category' => 'sport'],
                ],
            ),
        );
        $this->assertSame(
            'unknown',
            $canonicalCategory->invoke($controller, null),
        );
    }

    public function test_descriptor_prompt_rejects_overlay_and_unverified_model_evidence(): void
    {
        $ai = app(\App\Services\NineRouterAiService::class);
        $promptMethod = new ReflectionMethod($ai, 'descriptorPrompt');
        $prompt = mb_strtolower($promptMethod->invoke($ai));

        $this->assertStringContainsString('ignore watermarks', $prompt);
        $this->assertStringContainsString(
            'never copy them into visible_text',
            $prompt,
        );
        $this->assertStringContainsString(
            'never autocomplete blurred or partially hidden text',
            $prompt,
        );
        $this->assertStringContainsString(
            'never infer the total number of brake discs',
            $prompt,
        );
        $this->assertStringContainsString(
            'at least two independent reliable cues',
            $prompt,
        );
        $this->assertStringContainsString(
            'silently compare the closest visually similar models',
            $prompt,
        );

        // The production prompt must remain generic. The expected fixture
        // answer and local filename must never be leaked to the provider.
        $this->assertStringNotContainsString('kawasaki-zx6r', $prompt);
        $this->assertStringNotContainsString('zx-6r', $prompt);
        $this->assertStringNotContainsString('zx-25r', $prompt);
    }

    public function test_retrieval_document_focuses_on_motorcycle_without_mutating_json(): void
    {
        $ai = app(\App\Services\NineRouterAiService::class);
        $descriptor = [
            'brand_guess' => 'Ducati',
            'model_guess' => 'Panigale V4',
            'category' => 'sport',
            'primary_color' => 'red',
            'secondary_colors' => ['black'],
            'fairing' => 'full fairing',
            'windshield' => 'clear',
            'wheel_color' => 'black',
            'decals' => ['ducati'],
            'accessories' => [],
            'visible_text' => ['V4'],
            'distinctive_features' => ['single-sided swingarm'],
            'rider_clothing' => 'bright yellow jacket',
            'helmet' => [
                'type' => 'full-face',
                'primary_color' => 'black',
                'secondary_colors' => [],
                'graphics' => [],
            ],
        ];
        $original = $descriptor;

        $document = $ai->retrievalDescriptor($descriptor);

        $this->assertStringContainsString('motorcycle brand: ducati', $document);
        $this->assertStringContainsString('motorcycle model: panigale v4', $document);
        $this->assertStringContainsString('motorcycle visible text: v4', $document);
        $this->assertStringNotContainsString('bright yellow jacket', $document);
        $this->assertSame($original, $descriptor);
    }

    public function test_helmet_is_secondary_bonus_and_cannot_override_motor_conflict(): void
    {
        $service = app(MotorPhotoSearchService::class);
        $identityMethod = new ReflectionMethod($service, 'identityEvidence');
        $helmetMethod = new ReflectionMethod($service, 'helmetEvidence');
        $rankingMethod = new ReflectionMethod($service, 'rankingScore');

        $query = [
            'brand_guess' => 'kawasaki',
            'model_guess' => 'ninja zx-6r',
            'category' => 'sport',
            'primary_color' => 'green',
            'helmet' => [
                'present' => true,
                'type' => 'full-face',
                'primary_color' => 'red',
                'secondary_colors' => ['black'],
                'graphics' => ['white lightning pattern'],
                'visor' => 'dark smoke',
                'brand_guess' => 'shoei',
                'visible_text' => ['shoei'],
                'distinctive_features' => ['red rear spoiler'],
            ],
            'rider_helmet' => 'red and black shoei full-face helmet',
        ];

        $sameMotorSameHelmet = $query;

        $sameMotorDifferentHelmet = [
            ...$query,
            'helmet' => [
                'present' => true,
                'type' => 'open-face',
                'primary_color' => 'white',
                'secondary_colors' => [],
                'graphics' => [],
                'visor' => 'clear',
                'brand_guess' => 'unknown',
                'visible_text' => [],
                'distinctive_features' => [],
            ],
            'rider_helmet' => 'plain white open-face helmet',
        ];

        $conflictingMotorSameHelmet = [
            ...$query,
            'model_guess' => 'ninja zx-25r',
        ];

        $sameIdentity = $identityMethod->invoke(
            $service,
            $query,
            $sameMotorSameHelmet,
        );
        $differentHelmetIdentity = $identityMethod->invoke(
            $service,
            $query,
            $sameMotorDifferentHelmet,
        );
        $conflictIdentity = $identityMethod->invoke(
            $service,
            $query,
            $conflictingMotorSameHelmet,
        );

        $sameHelmet = $helmetMethod->invoke(
            $service,
            $query,
            $sameMotorSameHelmet,
        );
        $differentHelmet = $helmetMethod->invoke(
            $service,
            $query,
            $sameMotorDifferentHelmet,
        );
        $conflictHelmet = $helmetMethod->invoke(
            $service,
            $query,
            $conflictingMotorSameHelmet,
        );

        $visualScore = 0.78;
        $sameHelmetRank = $rankingMethod->invoke(
            $service,
            $visualScore,
            $sameIdentity,
            $sameHelmet['score'],
        );
        $differentHelmetRank = $rankingMethod->invoke(
            $service,
            $visualScore,
            $differentHelmetIdentity,
            $differentHelmet['score'],
        );
        $conflictRankWithHelmet = $rankingMethod->invoke(
            $service,
            $visualScore,
            $conflictIdentity,
            $conflictHelmet['score'],
        );
        $conflictRankWithoutHelmet = $rankingMethod->invoke(
            $service,
            $visualScore,
            $conflictIdentity,
            0.0,
        );

        $this->assertGreaterThan(
            $differentHelmet['score'],
            $sameHelmet['score'],
        );
        $this->assertGreaterThan($differentHelmetRank, $sameHelmetRank);
        $this->assertSame('conflict', $conflictIdentity['model']);
        $this->assertSame(
            $conflictRankWithoutHelmet,
            $conflictRankWithHelmet,
        );
        $this->assertGreaterThan($conflictRankWithHelmet, $sameHelmetRank);
    }
}
