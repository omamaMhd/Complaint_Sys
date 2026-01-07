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
        Schema::create('system_traces', function (Blueprint $table) {
            $table->id();
             $table->uuid('trace_id');
    $table->foreignId('user_id')->nullable();
    $table->string('user_role')->nullable();
    $table->string('action');
    $table->string('entity')->nullable();
    $table->unsignedBigInteger('entity_id')->nullable();
    $table->json('context')->nullable();
    $table->integer('duration_ms')->nullable();
    $table->string('status')->default('success');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_traces');
    }
};
