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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignUuid('user_tailor_id')->constrained("user_tailors", "uuid")->onUpdate(
                'cascade'
            )->onDelete('cascade');
            $table->string('transaction_code')->index();
            $table->float('gross_amount');
            $table->string('currency')->default('IDR');
            $table->string('category');
            $table->enum('status', [1, 2, 3])->default(1);
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
        Schema::dropIfExists('transactions');
    }
};
