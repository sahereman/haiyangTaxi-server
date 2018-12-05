<?php

namespace App\Handlers;

use GuzzleHttp\Client;


/**
 * 腾讯地图服务 处理类
 * Class TencentLBSHandler
 * @package App\Handlers
 */
class TencentMapHandler
{

    /**
     * 坐标位置描述 (逆地址解析,提供lat,lng)
     */
    public function reverseGeocoder($lat, $lng)
    {

        $client = new Client();


        $response = $client->request('GET', 'https://apis.map.qq.com/ws/geocoder/v1', [
            'query' =>
                [
                    'key' => config('services.tencentMapKey'),
                    'location' => $lat . ',' . $lng,
                    'get_poi' => 1,
                ]
        ]);


        return $response->getBody()->getContents();
    }


}