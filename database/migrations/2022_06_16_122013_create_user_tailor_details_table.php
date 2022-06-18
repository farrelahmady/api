<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('user_tailor_details', function (Blueprint $table) {
      $table->id();
      $table->foreignId('tailor_id')->references('id')->on('user_tailors');
      $table->string('first_name');
      $table->string('last_name');
      $table->string('profile_picture')->nullable()->default('/tailor/profile/default.png');
      $table->string('address')->nullable();
      $table->string('phone_number', 15)->nullable();
      $table->string('speciality');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('user_tailor_details');
  }
};
