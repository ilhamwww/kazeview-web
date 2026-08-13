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
}