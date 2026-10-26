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
        Schema::create('foods', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->text('ingredients')->nullable();
                $table->string('video_url', 500)->nullable();
                $table->string('video_copyright', 255)->nullable();   // e.g. "Video by Kemendikbud"
                $table->string('image_path', 500)->nullable();
                $table->timestamps();

            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
