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
        Schema::create('midtrans', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("approval_code")->nullable();
            $table->string("transaction_id")->nullable();
            $table->string("transaction_status")->nullable();
            $table->dateTime("transaction_time")->nullable();
            $table->float("gross_amount")->nullable();
            $table->string("currency")->default("IDR")->nullable();
            $table->string("order_id");
            $table->foreign("order_id")->on("transactions")->references("transaction_code")->onUpdate("cascade")->onDelete("cascade");
            $table->string("merchant_id")->nullable();
            $table->string("payment_type")->nullable();
            $table->string("signature_key")->nullable();
            $table->string("fraud_status")->nullable();
            $table->string("settlement_time")->nullable();
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
        Schema::dropIfExists('midtrans');
        Schema::table('midtrans', function (Blueprint $table) {
            $table->dropForeign(['transaction_table_id']);
        });
    }
};
