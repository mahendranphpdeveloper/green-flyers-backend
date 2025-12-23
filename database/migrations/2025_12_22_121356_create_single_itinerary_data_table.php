<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SingleItineraryData', function (Blueprint $table) {

            $table->id('SINo'); // Primary Key

            $table->unsignedBigInteger('ItineraryId');
            $table->unsignedBigInteger('userId');

            $table->timestamp('uploadData')->nullable();

            $table->string('certificateFile')->nullable();
            $table->string('approvelStatus')->nullable();

            $table->integer('emissionOffset')->nullable();
            $table->integer('treesPlanted')->nullable();

            $table->string('projectTypes')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('ItineraryId')
                  ->references('ItineraryId')
                  ->on('ItineraryData')
                  ->onDelete('cascade');

            $table->foreign('userId')
                  ->references('userId')
                  ->on('UserData')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SingleItineraryData');
    }
};
