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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id(); 
            $table->string('type'); // status_changed, note_added, complaint_created
            $table->morphs('notifiable'); // citizen_id
            $table->text('data'); // JSON
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        
            $table->index(['notifiable_id', 'notifiable_type']);
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
