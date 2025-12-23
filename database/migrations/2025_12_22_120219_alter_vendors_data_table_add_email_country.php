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
        Schema::table('VendorsData', function (Blueprint $table) {
            $table->string('email')->nullable()->after('projectUrl');
            $table->string('country')->nullable()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('VendorsData', function (Blueprint $table) {
            $table->dropColumn(['email', 'country']);
        });
    }
};
