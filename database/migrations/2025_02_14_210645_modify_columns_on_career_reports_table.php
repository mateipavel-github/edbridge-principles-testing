<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('career_reports', function (Blueprint $table) {
            // Ensure you have the doctrine/dbal package installed to change column types.
            $table->mediumText('processed_template')->nullable()->change();
            $table->mediumText('generation_log')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('career_reports', function (Blueprint $table) {
            $table->text('processed_template')->nullable()->change();
            $table->text('generation_log')->nullable()->change();
        });
    }
};
