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
        Schema::table('users', function (Blueprint $table) {
            $table->string('company')->nullable()->after('email');
            $table->string('role_title')->nullable()->after('company');
            $table->string('intent', 200)->nullable()->after('role_title');
            $table->string('linkedin_url')->nullable()->after('intent');
            $table->string('phone')->nullable()->after('linkedin_url');
            $table->boolean('is_organizer')->default(false)->after('phone');
            $table->boolean('is_invisible')->default(false)->after('is_organizer');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['company', 'role_title', 'intent', 'linkedin_url', 'phone', 'is_organizer', 'is_invisible']);
        });
    }
};
