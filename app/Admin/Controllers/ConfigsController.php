<?php

namespace App\Admin\Controllers;

use App\Handlers\ImageUploadHandler;
use App\Models\Config;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Form;
use Encore\Admin\Widgets\Tab;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ConfigsController extends Controller
{

    /**
     * Index interface.
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $configs = Config::configs()->where('parent_id', '!=', 0)->sortBy('sort')->values()->toArray();
        $config_groups = Config::configs()->where('parent_id', 0)->sortBy('sort')->values()->toArray();

        $tab = new Tab();
        foreach ($config_groups as $group)
        {
            $form = new Form();
            $form->action('configs/submit');

            foreach ($configs as $config)
            {
                if ($config['parent_id'] == $group['id'])
                {
                    switch ($config['type'])
                    {
                        case 'text':
                            if (!empty($config['help']))
                            {
                                $form->text("$config[code]", $config['name'])->default($config['value'])->help($config['help']);
                            } else
                            {
                                $form->text("$config[code]", $config['name'])->default($config['value']);
                            }
                            break;
                        case 'radio':
                            $option_arr = array_pluck($config['select_range'], 'name', 'value');
                            if (!empty($config['help']))
                            {
                                $form->radio("$config[code]", $config['name'])->options($option_arr)->default($config['value'])->help($config['help']);
                            } else
                            {
                                $form->radio("$config[code]", $config['name'])->options($option_arr)->default($config['value']);
                            }
                            break;
                        case 'image':
                            if (!empty($config['help']))
                            {
                                $form->image("$config[code]", $config['name'])->setWidth(4)->help($config['help']);
                            } else
                            {
                                $form->image("$config[code]", $config['name'])->setWidth(4);
                            }
                            if (!empty($config['value']))
                            {
                                $image_url = \Storage::disk('public')->url($config['value']);
                                $form->display("")->setWidth(1)->default("<img width='100%' src='$image_url' />");
                            }
                            break;
                    }
                }
            }
            $form->hidden('_token')->default(csrf_token());
            $tab->add($group['name'], $form->render());
        }

        return $content
            ->header('系统设置')
            ->body($tab->render());
    }

    public function submit(Request $request, ImageUploadHandler $imageUploadHandler)
    {
        $data = $request->except(['_token']);
        $configs = Config::configs();

        foreach ($data as $key => $value)
        {
            if ($request->has($key) && $configs->where('code', $key)->first()->value != $value)
            {
                if ($request->hasFile($key))
                {
                    $value = $imageUploadHandler->uploadOriginal($request->file($key));
                }
                $config = Config::where('code', $key)->first();
                $config->value = $value;
                $config->save();
            }
        }

        admin_toastr(trans('admin.save_succeeded'));
        return redirect()->back();
    }
}
