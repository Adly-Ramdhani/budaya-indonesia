<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('composer', 255)->nullable();
            $table->string('language', 100)->nullable();   // e.g. Acehnese, Javanese
            $table->text('description')->nullable();
            $table->text('lyrics')->nullable();
            $table->string('audio_path', 500)->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
