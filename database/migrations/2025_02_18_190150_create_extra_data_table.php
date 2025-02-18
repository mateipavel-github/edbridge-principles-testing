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
        Schema::create('onet__extra_data', function (Blueprint $table) {
            $table->id();
            $table->char('onet_soc_code', 10)->unique();
            $table->integer('employment')->nullable();
            $table->integer('projected_employment')->nullable();
            $table->float('projected_growth')->nullable();
            $table->integer('projected_annual_openings')->nullable();
            $table->float('median_hourly_wage')->nullable();
            $table->float('median_annual_wage')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onet__extra_data');
    }
};
