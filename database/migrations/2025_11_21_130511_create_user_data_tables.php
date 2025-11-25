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
        Schema::create('UserData', function (Blueprint $table) {
            $table->id('userId');
            $table->string('userName')->nullable();
            $table->string('userEmail')->nullable();
            $table->string('userPassword')->nullable();
            $table->string('profilePic')->nullable();
            $table->string('lastModification')->nullable();
            $table->timestamps();
        });

        Schema::create('ItineraryData', function (Blueprint $table) {
            $table->id('ItineraryId');
            $table->foreignId('userId')->constrained('UserData', 'userId')->onDelete('cascade');
            $table->timestamp('date')->nullable();
            $table->string('airline')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('class')->nullable();
            $table->integer('passengers')->nullable();
            $table->string('tripType')->nullable();
            $table->string('distance')->nullable();
            $table->integer('emission')->nullable();
            $table->integer('offsetAmount')->nullable();
            $table->integer('offsetPercentage')->nullable();
            $table->string('status')->nullable();
            $table->string('approvelStatus')->nullable();
            $table->timestamps();
        });

        Schema::create('OffsetData', function (Blueprint $table) {
            $table->id('SINo');
            $table->foreignId('ItineraryId')->constrained('ItineraryData', 'ItineraryId')->onDelete('cascade');
            $table->foreignId('userId')->constrained('UserData', 'userId')->onDelete('cascade');
            $table->timestamp('uploadData')->nullable();
            $table->string('certificateFile')->nullable();
            $table->string('status')->nullable();
            $table->integer('emissionOffset')->nullable();
            $table->integer('treesPlanted')->nullable();
            $table->timestamps();
        });

        Schema::create('VendorsData', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('projects')->nullable();
            $table->string('status')->nullable();
            $table->text('description')->nullable();
            $table->string('projectUrl')->nullable();
            $table->string('state')->nullable();
            $table->timestamps();
        });

        Schema::create('AdminData', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('AdminData');
        Schema::dropIfExists('VendorsData');
        Schema::dropIfExists('OffsetData');
        Schema::dropIfExists('ItineraryData');
        Schema::dropIfExists('UserData');
    }
};
