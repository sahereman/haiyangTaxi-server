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
     * @param $lat
     * @param $lng
     * @return string
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

    /**
     * 距离计算
     * @param $from &Array['lat','lng']
     * @param $to_array &Array[['lat','lng'],['lat','lng']]
     * @return string
     */
    public function calculateDistance($from, $to_array)
    {
        $client = new Client();

        $to_locations = '';

        foreach ($to_array as $item)
        {
            $to_locations .= $item['lat'] . ',' . $item['lng'] . ';';
        }

        $response = $client->request('GET', 'https://apis.map.qq.com/ws/distance/v1', [
            'query' =>
                [
                    'key' => config('services.tencentMapKey'),
                    'mode' => 'driving',
                    'from' => $from['lat'] . ',' . $from['lng'],
                    'to' => '36.092484,120.380966;36.092484,120.380966',
                ]
        ]);


        return $response->getBody()->getContents();
    }


}