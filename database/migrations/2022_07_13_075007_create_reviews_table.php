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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignUuid('user_customer_id')->constrained('user_customers', 'uuid')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignUuid('user_tailor_id')->constrained("user_tailors", "uuid")->onUpdate('cascade')->onDelete('cascade');
            $table->string('review')->nullable();
            $table->tinyInteger('rating');
            $table->text('message')->nullable();
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
        Schema::dropIfExists('reviews');
    }
};
