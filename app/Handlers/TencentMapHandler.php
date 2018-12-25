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

    //$location_array = $map->generateCalculateDistanceParam2FromDrivers($drivers);
    //$calc_res = $map->calculateDistance($set->from_location, $location_array);
    //$drivers = $map->extendDriversFromMapDistance($drivers, $calc_res);
    //$drivers = $map->findDistanceRangeDrivers($drivers, 0, 2000); // 通知2000米以内的车辆

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

    //    /**
    //     * 距离计算
    //     * @param $from &Array['lat','lng']
    //     * @param $to_array &Array[['lat','lng'],['lat','lng']]
    //     * @return string
    //     */
    //    public function calculateDistance($from, $to_array)
    //    {
    //        $client = new Client();
    //
    //        $to_locations = '';
    //
    //        foreach ($to_array as $item)
    //        {
    //            if (end($to_array) == $item)
    //            {
    //                $to_locations .= $item['lat'] . ',' . $item['lng'];
    //
    //            } else
    //            {
    //                $to_locations .= $item['lat'] . ',' . $item['lng'] . ';';
    //            }
    //        }
    //
    //        $response = $client->request('GET', 'https://apis.map.qq.com/ws/distance/v1', [
    //            'query' =>
    //                [
    //                    'key' => config('services.tencentMapKey'),
    //                    'mode' => 'driving',
    //                    'from' => $from['lat'] . ',' . $from['lng'],
    //                    'to' => $to_locations,
    //                ]
    //        ]);
    //
    //
    //        return $response->getBody()->getContents();
    //    }
    //
    //    /**
    //     * 辅助函数 格式化请求距离运算的ToArray数据格式,数据源:Drivers
    //     * @param $drivers
    //     * @return array
    //     */
    //    public function generateCalculateDistanceParam2FromDrivers($drivers)
    //    {
    //        $array = array();
    //
    //        foreach ($drivers as $key => $driver)
    //        {
    //            $array[] = ['lat' => $driver['lat'], 'lng' => $driver['lng']];
    //        }
    //
    //        return $array;
    //    }
    //
    //
    //    /**
    //     * 辅助函数 扩展Drivers带有距离单位 ,数据源:Drivers , 距离计算Api返回的Content
    //     * @param $drivers
    //     * @param $calc_res
    //     * @return array
    //     */
    //    public function extendDriversFromMapDistance($drivers, $calc_res)
    //    {
    //        $calc_array = json_decode($calc_res, true);
    //        $drivers_array = array();
    //
    //        foreach ($drivers as $key => $driver)
    //        {
    //            $drivers_array[$key] = $driver;
    //            $drivers_array[$key]['distance'] = $calc_array['result']['elements'][$key]['distance'];
    //            $drivers_array[$key]['duration'] = $calc_array['result']['elements'][$key]['duration'];
    //        }
    //
    //        return $drivers_array;
    //    }
    //
    //    /**
    //     * 辅助函数 返回距离范围内的Drivers
    //     * @param $drivers
    //     * @param $startDistance & 起始距离单位(米)
    //     * @param $endDistance & 结束距离单位(米)
    //     * @return array'
    //     */
    //    function findDistanceRangeDrivers($drivers, $startDistance, $endDistance)
    //    {
    //        $driver_array = array();
    //
    //        foreach ($drivers as $driver)
    //        {
    //            if ($driver['distance'] >= $startDistance && $driver['distance'] <= $endDistance)
    //            {
    //                $driver_array[] = $driver;
    //            }
    //        }
    //
    //        return $driver_array;
    //    }

}