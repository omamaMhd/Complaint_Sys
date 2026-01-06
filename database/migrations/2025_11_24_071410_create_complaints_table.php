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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
             $table->foreignId('citizen_id')->nullable()->constrained('citizens')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type');
            $table->string('location')->nullable();
             //$table->string('responsible_party')->nullable();
             $table->enum('responsible_party', [
    'وزارة الداخلية',
    'وزارة الصحة',
    'وزارة التربية والتعليم',
    'وزارة النقل',
    'وزارة المالية',
    'البلدية',
    'هيئة المياه',
    'هيئة الكهرباء',
    'شرطة المرور'
             ])->nullable();
            $table->text('description');

           $table->enum('status', ['new', 'in_progress', 'completed', 'rejected'])->default('new');

            $table->string('reference_number')->unique();

            // Concurrency lock
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
