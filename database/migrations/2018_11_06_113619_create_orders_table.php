<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedInteger('driver_id');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');

            $table->string('order_sn')->unique()->comment('订单号');
            $table->string('status')->comment('行程状态')->index();
            $table->string('trip')->nullable()->comment('进行中订单阶段');


            $table->string('from_address')->comment('出发地址');
            $table->string('from_location')->comment('出发lat & lng');
            $table->string('to_address')->comment('到达地址');
            $table->string('to_location')->comment('到达lat & lng');

            $table->string('close_from')->nullable()->comment('订单关闭者');
            $table->string('close_reason')->nullable()->comment('订单关闭原因');

            $table->timestamp('closed_at')->nullable()->comment('取消时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
