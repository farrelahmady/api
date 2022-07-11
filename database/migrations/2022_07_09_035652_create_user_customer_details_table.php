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
    Schema::create('user_customer_details', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_customer_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
      $table->string('first_name');
      $table->string('last_name');
      $table->text('profile_picture')->nullable();
      $table->text('address')->nullable();
      $table->string('phone_number')->nullable();
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
    Schema::dropIfExists('user_customer_details');
    Schema::table('user_customer_details', function (Blueprint $table) {
      $table->dropForeign(['user_customer_id']);
    });
  }
};
