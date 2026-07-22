<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('about_settings')) {
            return;
        }

        Schema::create('about_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('eyebrow')->default('ABOUT KAZEVIEW');
            $table->string('headline')->default('MOTION, PEOPLE, AND MACHINES.');
            $table->text('intro')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('story_title')->default('BUILT AROUND THE MOMENT.');
            $table->text('story_body')->nullable();
            $table->string('story_image')->nullable();
            $table->jsonb('capabilities')->nullable();
            $table->string('location')->default('YOGYAKARTA, INDONESIA');
            $table->string('established')->nullable();
            $table->string('cta_title')->default('LET’S CREATE SOMETHING IN MOTION.');
            $table->string('cta_label')->default('BOOK A SHOOT');
            $table->string('cta_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_settings');
    }
};