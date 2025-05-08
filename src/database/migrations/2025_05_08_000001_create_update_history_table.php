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
        Schema::create('update_history', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('applied_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('applied_at');
            $table->boolean('successful')->default(true);
            $table->string('backup_id')->nullable();
            $table->timestamps();
            
            // Index on version for quick lookups
            $table->index('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_history');
    }
};
