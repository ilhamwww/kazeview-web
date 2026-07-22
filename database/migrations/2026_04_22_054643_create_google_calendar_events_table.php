<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('google_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('google_event_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_events');
    }
};
