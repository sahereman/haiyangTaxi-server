<?php

namespace App\Admin\Controllers;

use App\Http\Requests\Request;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;

class DriversController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('司机管理')
            ->description('列表')
            ->body($this->grid());
    }

    /**
     * Show interface.
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('司机管理')
            ->description('详情')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('司机管理')
            ->description('编辑')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('司机管理')
            ->description('新增')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Driver());
        $grid->model()->orderBy('last_active_at', 'desc'); // 设置初始排序条件

        /*筛选*/
        $grid->filter(function ($filter) {
            $filter->disableIdFilter(); // 去掉默认的id过滤器
            $filter->like('cart_number', '车牌号');
        });

        $grid->id('ID');
        $grid->cart_number('车牌号');
        $grid->name('联系人');
        $grid->phone('手机号');
        $grid->orders('今日接单数')->where('created_at', '>', today())->where('created_at', '<', today()->addDay()->subSecond())->display(function ($orders) {
            $count = count($orders);
            return "<span class='label label-success'>{$count}</span>";
        });
        $grid->order_count('总接单数')->sortable();
        $grid->last_active_at('最后活跃时间')->sortable();
        $grid->equipments('可用的IMEI设备')->display(function ($equipments) {
            return count($equipments);
        });
        return $grid;
    }

    /**
     * Make a show builder.
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Driver::findOrFail($id));

        $show->id('ID');

        $show->cart_number('车牌号');
        $show->name('联系人');
        $show->phone('手机号');
        $show->remark('备注');
        $show->order_count('总接单数');
        $show->created_at('创建时间');
        $show->updated_at('更新时间');

        $show->equipments('设备管理', function ($equ) {
            /*禁用*/
            $equ->disableActions();
            $equ->disableRowSelector();
            $equ->disableExport();
            $equ->disableFilter();
            $equ->disableCreateButton();
            $equ->disablePagination();

            /*属性*/
            $equ->imei('设备IMEI码');
            $equ->created_at('创建时间');
            $equ->updated_at('更新时间');
        });

        return $show;
    }

    /**
     * Make a form builder.
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Driver());

        $form->text('cart_number', '车牌号')->rules('required');
        $form->text('name', '联系人')->rules('required');
        $form->text('phone', '手机号')->rules('required');
        $form->textarea('remark', '备注');
        $form->text('order_count', '总接单数')->default(0);

        $form->hasMany('equipments', '设备管理', function (Form\NestedForm $form) {
            $form->text('imei', '设备IMEI码')->required();
        });

        return $form;
    }
}
