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
    Schema::create('citizens', function (Blueprint $table) {
        $table->id();
        $table->string('username')->nullable();
        $table->string('mobile')->unique(); 
        $table->string('password');
        $table->integer('verification_code')->nullable();
        $table->timestamp('code_expires_at')->nullable();
        $table->boolean('is_verified')->default(false);
        $table->string('fcm_token')->nullable();
        $table->timestamps();
        $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citizens');
    }
};
