<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCityHotAddressesTable extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('city_hot_addresses', function (Blueprint $table) {
            $table->increments('id');

            $table->string('city')->nullable()->comment('城市名');
            $table->string('address_component')->nullable()->comment('地址描述');
            $table->string('address')->nullable()->comment('地址名');
            $table->string('location')->nullable()->comment('地址lat & lng');
            $table->unsignedSmallInteger('sort')->nullable(false)->default(0)->comment('排序值');

        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('city_hot_addresses');
    }
}
