<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriversTable extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->increments('id');

            $table->string('cart_number')->comment('车牌号');
            $table->string('name')->comment('司机');
            $table->string('phone')->comment('手机号');
            $table->text('remark')->nullable()->comment('备注');
            $table->unsignedInteger('order_count')->default(0)->comment('订单总数');
            $table->timestamp('last_active_at')->nullable()->comment('最后活跃时间');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivers');
    }
}
