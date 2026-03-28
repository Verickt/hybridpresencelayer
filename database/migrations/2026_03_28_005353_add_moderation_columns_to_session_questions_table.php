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
        Schema::table('session_questions', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('is_answered');
            $table->boolean('is_hidden')->default(false)->after('is_pinned');
            $table->foreignId('answered_by')->nullable()->after('is_hidden')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('answered_by');
            $table->dropColumn(['is_pinned', 'is_hidden']);
        });
    }
};
