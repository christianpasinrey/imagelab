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
        Schema::table('images', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
            $table->string('slug')->unique()->nullable()->after('title');
            $table->json('tags')->nullable()->after('slug');
            $table->string('session_id')->nullable()->after('tags');
            $table->unsignedInteger('views_count')->default(0)->after('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn(['title', 'slug', 'tags', 'session_id', 'views_count']);
        });
    }
};
