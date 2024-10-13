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
        Schema::create('ingest_rule_presets', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->integer('ingest_rule_id')->unique();
            $table->foreign('ingest_rule_id')->references('id')->on('ingest_rules');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingest_rule_presets');
    }
};
