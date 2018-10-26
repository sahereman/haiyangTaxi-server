<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfigsTable extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('parent_id')->default(0)->comment('配置父级id');
            $table->string('name')->default('')->comment('配置名称');
            $table->string('code')->unique()->nullable()->comment('配置Code 与 设置在表单中的name字段');
            $table->string('type')->default('group')->comment('配置类型:group 组(父级类型) | text 文本输入框 | radio 单选框 | image 图片');

            //radio : [['value' => 0, 'name' => '开启'], ['value' => 1, 'name' => '关闭']]
            $table->string('select_range')->default('')->comment('设置在表单中的选项范围');


            $table->string('value')->default('')->comment('配置的值');
            $table->string('help')->default('')->comment('帮助提示');
            $table->smallInteger('sort')->default(0)->comment('排序');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('configs');
    }
}
