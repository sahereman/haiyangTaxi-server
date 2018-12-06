<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderSetsTable extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('order_sets', function (Blueprint $table) {
            $table->string('key')->primary();

            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('from_address')->comment('出发地址');
            $table->string('from_location')->comment('出发lat & lng');
            $table->string('to_address')->comment('到达地址');
            $table->string('to_location')->comment('到达lat & lng');

            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_sets');
    }
}
