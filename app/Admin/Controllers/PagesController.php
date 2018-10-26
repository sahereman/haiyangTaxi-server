<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

class PagesController extends Controller
{

    public function index(Content $content)
    {
        return $content
            ->header('首页')
            ->description('数据统计')
            ->body(view('admin.pages.root'));
    }


    public function dashboard(Content $content)
    {
        return $content
            ->header('系统信息')
            ->description('信息')
            ->row(function (Row $row) {

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::environment());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::extensions());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::dependencies());
                });
            });
    }
}
