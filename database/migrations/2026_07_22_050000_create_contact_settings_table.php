<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contact_settings')) {
            return;
        }

        Schema::create('contact_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('eyebrow')->default('CONTACT KAZEVIEW');
            $table->string('headline')->default('LET’S MAKE SOMETHING MOVE.');
            $table->text('intro')->nullable();
            $table->string('image')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('location')->default('YOGYAKARTA, INDONESIA');
            $table->string('availability')->default('AVAILABLE FOR SELECTED PROJECTS');
            $table->string('response_time')->default('USUALLY WITHIN 1–2 BUSINESS DAYS');
            $table->jsonb('social_links')->nullable();
            $table->string('cta_label')->default('START A CONVERSATION');
            $table->text('whatsapp_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_settings');
    }
};