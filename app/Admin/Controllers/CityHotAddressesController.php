<?php

namespace App\Admin\Controllers;

use App\Handlers\TencentMapHandler;
use App\Models\CityHotAddress;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Validation\Rule;

class CityHotAddressesController extends Controller
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
            ->header('城市热门地点管理')
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
            ->header('城市热门地点管理')
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
            ->header('城市热门地点管理')
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
            ->header('城市热门地点管理')
            ->description('新增')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CityHotAddress);
        $grid->model()->orderBy('city', 'desc'); // 设置初始排序条件


        /*筛选*/
        $grid->filter(function ($filter) {
            $filter->disableIdFilter(); // 去掉默认的id过滤器

            $filter->equal('city', '城市')->select(CityHotAddress::$cityMap);
        });


        $grid->city('城市');
        $grid->address('乘客常去目的地');
        $grid->location('目的地坐标')->display(function ($location) {
            return 'Lat : ' . $location['lat'] . ' Lng : ' . $location['lng'];
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
        $show = new Show(CityHotAddress::findOrFail($id));

        $show->id('ID');
        $show->city('城市');
        $show->address_component('地址描述');
        $show->address('乘客常去目的地');
        $show->location('目的地坐标')->as(function ($location) {
            return 'Lat : ' . $location['lat'] . ' Lng : ' . $location['lng'];
        });

        return $show;
    }

    /**
     * Make a form builder.
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CityHotAddress);

        $form->select('city', '城市')->options(CityHotAddress::$cityMap)->rules(function ($form) {
            return ['required', Rule::exists('city_hot_addresses', 'city')];
        });

        $form->text('address', '乘客常去目的地');
        $form->text('location.lat', '目的地坐标 Lat');
        $form->text('location.lng', '目的地坐标 Lng');


        //保存前回调
        $form->saving(function (Form $form) {
            $form->model()->location = ['lat' => $form->input('location.lat'), 'lng' => $form->input('location.lng')];


            $map = new TencentMapHandler();
            $address_component = json_decode($map->reverseGeocoder($form->input('location.lat'), $form->input('location.lng')), true);
            $form->model()->address_component = isset($address_component['result']['address']) ? $address_component['result']['address'] : '';
        });


        return $form;
    }
}
