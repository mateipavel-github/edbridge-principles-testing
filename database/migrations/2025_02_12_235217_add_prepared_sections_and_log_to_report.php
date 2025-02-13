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
        Schema::table('career_reports', function (Blueprint $table) {
            $table->text('processed_template')->nullable();
            $table->text('generation_log')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('career_reports', function (Blueprint $table) {
            $table->dropColumn(['processed_template', 'generation_log']);
        });
    }
};
