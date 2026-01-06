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
        Schema::create('complaint_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->onDelete('cascade');

            // يمكن أن يكون مواطن أو موظف أو نظام، لذلك nullable
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('performed_by_type')->nullable(); // 'user' أو 'admin'
$table->string('performed_by_name')->nullable(); // اسم الشخص الذي قام بالإجراء
            $table->string('action'); // created, locked, status_changed, attachment_added...

            $table->json('data')->nullable(); // old/new status, attachment id, reason, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_histories');
    }
};
