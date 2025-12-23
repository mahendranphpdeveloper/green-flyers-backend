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
        Schema::table('AdminData', function (Blueprint $table) {
            // Rename column
            $table->renameColumn('username', 'adminname');

            // Add new columns
            $table->string('email')->nullable()->after('password');
            $table->integer('otp')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('AdminData', function (Blueprint $table) {
            // Revert column name
            $table->renameColumn('adminname', 'username');

            // Remove added columns
            $table->dropColumn(['email', 'otp']);
        });
    }
};
