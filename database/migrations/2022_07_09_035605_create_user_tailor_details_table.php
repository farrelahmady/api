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
            $table->foreignId('user_tailor_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->text('profile_picture')->nullable();
            $table->text('place_picture')->nullable();
            $table->longText('description')->nullable();
            $table->text('address');
            $table->string('district');
            $table->string('city');
            $table->string('province');
            $table->string('zip_code');
            $table->string('phone_number')->nullable();
            $table->string('speciality')->nullable();
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
        Schema::table('user_tailor_details', function (Blueprint $table) {
            $table->dropForeign(['user_tailor_id']);
        });
    }
};
