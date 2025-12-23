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
        Schema::table('ItineraryData', function (Blueprint $table) {
            // Change existing columns type
            $table->integer('emission')->nullable()->change();
            $table->integer('offsetAmount')->nullable()->change();
            $table->integer('offsetPercentage')->nullable()->change();

            // Add new columns
            $table->string('flightcode')->nullable()->after('airline');
            $table->string('originCity')->nullable()->after('origin');
            $table->string('destinationCity')->nullable()->after('destination');
            $table->integer('numberOfTrees')->nullable()->after('offsetPercentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ItineraryData', function (Blueprint $table) {
            // Revert column types
            $table->double('emission')->nullable()->change();
            $table->double('offsetAmount')->nullable()->change();
            $table->double('offsetPercentage')->nullable()->change();

            // Drop added columns
            $table->dropColumn([
                'flightcode',
                'originCity',
                'destinationCity',
                'numberOfTrees'
            ]);
        });
    }
};
