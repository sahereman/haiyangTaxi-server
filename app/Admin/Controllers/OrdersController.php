<?php

namespace App\Admin\Controllers;

use App\Models\Order;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class OrdersController extends Controller
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
            ->header('订单管理')
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
            ->header('订单管理')
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
            ->header('订单管理')
            ->description('编辑')
            ->body($this->form()->edit($id));
    }

    /**
     * Make a grid builder.
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order);
        $grid->model()->orderBy('created_at', 'desc'); // 设置初始排序条件
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
        });

        /*筛选*/
        $grid->filter(function ($filter) {
            $filter->disableIdFilter(); // 去掉默认的id过滤器

            $filter->equal('status', '订单状态')->select(Order::$orderStatusMap);
            $filter->like('order_sn', '订单号');
            $filter->where(function ($query) {
                $query->whereHas('user', function ($query) {
                    $query->where('phone', 'like', "%{$this->input}%");
                });
            }, '乘客(手机号)');

            $filter->where(function ($query) {
                $query->whereHas('driver', function ($query) {
                    $query->where('cart_number', 'like', "%{$this->input}%");
                });
            }, '承运车辆(车牌号)');
        });

        $grid->column('user.phone', '乘客');
        $grid->column('driver.cart_number', '承运车辆');
        $grid->order_sn('订单号');

        $grid->status('状态')->display(function ($value) {
            if ($value == Order::ORDER_STATUS_TRIPPING)
            {
                return '<span style="color: red;">' . Order::$orderStatusMap[$value] . '</span>';
            }
            return Order::$orderStatusMap[$value] ?? '未知';
        });

        $grid->from_address('出发地');
        $grid->to_address('目的地');
        $grid->created_at('创建时间')->sortable();

        return $grid;
    }

    /**
     * Make a show builder.
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Order::findOrFail($id));
        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
        });

        $show->id('ID');
        $show->order_sn('订单号');
        $show->status('订单状态')->as(function ($status) {
            return Order::$orderStatusMap[$status];
        });

        $show->from_address('出发地');
        $show->from_location('出发地坐标')->unescape()->as(function ($from_location) {
            return 'Lat : ' . $from_location['lat'] . '<br />Lng : ' . $from_location['lng'];
        });
        $show->to_address('目的地');
        $show->to_location('出发地坐标')->unescape()->as(function ($to_location) {
            return 'Lat : ' . $to_location['lat'] . '<br />Lng : ' . $to_location['lng'];
        });
        $show->created_at('创建时间');
        $show->updated_at('更新时间');


        // 订单是否取消
        if ($show->getModel()->status == Order::ORDER_STATUS_CLOSED)
        {
            $show->close_from('订单取消方')->as(function ($close_from) {
                return Order::$orderCloseFromMap[$close_from];
            });
            $show->closed_at('订单取消时间');
        }

        // 订单是否完成
        if ($show->getModel()->status == Order::ORDER_STATUS_COMPLETED)
        {
            $show->completed_at('订单完成时间');
        }

        $show->user('乘客信息', function ($user) {
            $user->panel()->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableList();
                $tools->disableDelete();
            });
            $user->phone('手机号');
            $user->last_active_at('最后活跃时间');
        });

        $show->driver('承运人信息', function ($driver) {
            $driver->panel()->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableList();
                $tools->disableDelete();
            });
            $driver->name('姓名');
            $driver->phone('手机号');
            $driver->cart_number('车牌号');
            $driver->order_count('总接单量');
            $driver->last_active_at('最后活跃时间');

        });

        return $show;
    }

}
