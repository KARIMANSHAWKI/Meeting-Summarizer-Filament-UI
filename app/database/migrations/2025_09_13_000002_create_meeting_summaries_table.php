<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meeting_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->text('summary');
            $table->json('decisions')->nullable();
            $table->json('action_items')->nullable();
            $table->string('source')->nullable();
            $table->json('azure_raw')->nullable();
            $table->enum('input_type', ['text', 'media']);
            $table->longText('input_text')->nullable();
            $table->string('input_media_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_summaries');
    }
};
