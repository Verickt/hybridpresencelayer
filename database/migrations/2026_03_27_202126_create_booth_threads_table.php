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
        Schema::create('booth_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booth_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booth_demo_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20)->default('question');
            $table->string('body', 500);
            $table->boolean('is_answered')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->dateTime('follow_up_requested_at')->nullable();
            $table->dateTime('last_activity_at');
            $table->timestamps();

            $table->index(['booth_id', 'kind']);
            $table->index(['booth_id', 'is_pinned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booth_threads');
    }
};
