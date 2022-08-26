<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->uuid()->unique();
            $table->foreignUuid('user_customer_id')->constrained("user_customers", "uuid")->onUpdate('cascade')->onDelete('cascade');
            $table->foreignUuid('user_tailor_id')->constrained("user_tailors", "uuid")->onUpdate('cascade')->onDelete('cascade');
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
