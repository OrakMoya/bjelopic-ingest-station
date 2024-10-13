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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('filename');
            $table->string('path');
            $table->unique(['filename', 'path', 'volume_id']);

            $table->json('exif')->nullable();
            $table->string('mimetype');

            $table->foreignId('volume_id')
                ->references('id')
                ->on('volumes')
                ->onDelete('cascade');
            $table->boolean('is_dynamic')->default(false);
            $table->boolean('ingest_ignore')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
