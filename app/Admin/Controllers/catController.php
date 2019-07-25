<?php

namespace App\Admin\Controllers;

use App\Models\cat;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Tree;

class catController extends Controller
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
            ->header('Index')
            ->description('description')
            ->body($this->tree());
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
            ->header('Detail')
            ->description('description')
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
            ->header('Edit')
            ->description('description')
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
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new cat);

        $grid->id('Id');
        $grid->parent_id('Parent id');
        $grid->name('Name');

        return $grid;
    }

    /**
     * Make a show builder.
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(cat::findOrFail($id));

        $show->id('Id');
        $show->parent_id('Parent id');
        $show->name('Name');

        return $show;
    }

    protected function tree()
    {
        return cat::tree(function (Tree $tree) {
            $tree->branch(function ($branch) {
                return "ID:{$branch['id']} - {$branch['name']} " . '<span class="label label-success">图片数: ' . cat::find($branch['id'])->imgs->count().'</span>';
//                return "ID:{$branch['id']} - {$branch['name']} ";
            });
        });
    }

    /**
     * Make a form builder.
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new cat);

        $form->text('name', 'Name');

        return $form;
    }
}
