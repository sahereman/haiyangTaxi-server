<?php

namespace App\Handlers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageUploadHandler
{

    private $thumb_width = 240;/*缩略图宽度*/
    private $thumb_height = 240;/*缩略图高度*/

    private $clear_temp_odds = 1000;/*清空temp目录的几率 1000分之1*/

    /**
     * 解码完整资源路径,返回录入数据库路径
     * @param $internet_url
     * @return mixed
     */
    public static function decodeUrl($internet_url)
    {
        $slice_url = config('filesystems.disks.public.url') . '/';

        return str_replace($slice_url, '', $internet_url);
    }

    /**
     * 上传一个原始文件到original目录 （如有可选参数 指定目录及文件名）
     * @param $file & 表单的file对象
     * @param bool $save_path
     * @param bool $name
     * @return mixed & 返回需要入数据库的文件路径
     */
    public function uploadOriginal($file, $save_path = false, $name = false)
    {
        $date = Carbon::now();
        $child_path = 'original/' . date('Ym', $date->timestamp);/*存储文件格式为 201706 文件夹内*/

        if ($save_path && $name)
        {
            $path = Storage::disk('public')->putFileAs($save_path, $file, $name . strrchr($file->getClientOriginalName(), '.'));/*自己拼接保持原本上传的后缀名*/
        }
        else
        {
            $path = Storage::disk('public')->putFile($child_path, $file);
        }

        return $path;
    }

    /**
     * 上传一张缩略图到thumb目录 （缩略图尺寸根据类属性设定）（如有可选参数 指定目录及文件名）
     * @param $file & 表单的file对象
     * @param bool $save_path
     * @param bool $name
     * @return mixed 返回需要入数据库的文件路径
     */
    public function uploadThumb($file, $save_path = false, $name = false)
    {
        $date = Carbon::now();
        $prefix_path = Storage::disk('public')->getAdapter()->getPathPrefix();
        $child_path = 'thumb/' . date('Ym', $date->timestamp) ;/*存储文件格式为 201706 文件夹内*/

        if ($save_path && $name)
        {
            $path = Storage::disk('public')->putFileAs($save_path, $file, $name . strrchr($file->getClientOriginalName(), '.'));/*自己拼接保持原本上传的后缀名*/
        }
        else
        {
            $path = Storage::disk('public')->putFile($child_path, $file);
        }


        Image::make($prefix_path . $path)->resize($this->thumb_width, $this->thumb_height)->save();

        return $path;
    }

    /**
     * 上传一个临时文件到temp目录
     * @param $file
     * @return false|string
     */
    public function uploadTemp($file)
    {
        if (mt_rand(0, $this->clear_temp_odds) == 0)
        {
            $prefix_path = Storage::disk('public')->getAdapter()->getPathPrefix();
            self::truncateFolder($prefix_path . 'temp');
        }

        $path = Storage::disk('public')->putFile('temp', $file);

        return $path;
    }

    /**
     * 给定一个数据库中存储的文件路径,删除文件,返回 true 删除成功
     * @param $path
     * @return mixed
     */
    public function deleteFile($path)
    {
        return Storage::delete($path);/*返回 true 删除成功*/
    }

    /**
     * 删除所有子目录及目录中的文件(保留目录)
     * @param $path (物理地址的绝对路径文件夹)
     */
    public function truncateFolder($path)
    {
        $op = dir($path);
        while (false != ($item = $op->read()))
        {
            if ($item == '.' || $item == '..')
            {
                continue;
            }
            if (is_dir($op->path . '/' . $item))
            {
                self::truncateFolder($op->path . '/' . $item);
                rmdir($op->path . '/' . $item);
            }
            else
            {
                unlink($op->path . '/' . $item);
            }
        }
    }

}