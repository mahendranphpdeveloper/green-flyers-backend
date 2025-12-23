<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('SingleItineraryData', function (Blueprint $table) {
            // Only perform the rename if the column exists and is not already 'id'
            if (Schema::hasColumn('SingleItineraryData', 'SINo')) {
                $table->renameColumn('SINo', 'id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('SingleItineraryData', function (Blueprint $table) {
            if (Schema::hasColumn('SingleItineraryData', 'id')) {
                $table->renameColumn('id', 'SINo');
            }
        });
    }
};
