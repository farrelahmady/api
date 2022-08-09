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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_customer_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_tailor_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->date('date');
            $table->time('time');
            $table->longText('additional_message')->nullable();
            $table->enum('status', [1, 2, 3, 4, 5])->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};
