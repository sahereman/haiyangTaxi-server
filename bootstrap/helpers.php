<?php

function route_class()
{
    return str_replace('.', '-', Route::currentRouteName());
}


/**
 * 辅助函数 格式化active drivers 为 Drivers数组
 * @param $active_drivers
 * @return array & Drivers
 */
function formatActiveDrivers($active_drivers)
{
    $array = array();

    foreach ($active_drivers as $key => $driver)
    {
        $driver = json_decode($driver, true);
        $array[] = $driver;
    }

    return $array;
}


/**
 * 辅助函数 返回闲置的Drivers
 * @param $drivers
 * @return array
 */
function findFreeDrivers($drivers)
{
    $free_driver = array();

    foreach ($drivers as $driver)
    {
        if ($driver['status'] == \App\Sockets\WebSocket::DRIVER_STATUS_FREE)
        {
            $free_driver[] = $driver;
        }
    }

    return $free_driver;
}

/**
 * 辅助函数 以坐标差距算出附近的车辆
 * @param $drivers
 * @param $lat
 * @param $lng
 * @param float $location_deviation
 * @return array
 */
function findNearbyDrivers($drivers, $lat, $lng, $location_deviation = 0.002)
{
    $driver_array = array();

    $deviation_min_lat = bcsub($lat, $location_deviation, 6);
    $deviation_max_lat = bcadd($lat, $location_deviation, 6);

    $deviation_min_lng = bcsub($lng, $location_deviation, 6);
    $deviation_max_lng = bcadd($lng, $location_deviation, 6);

    foreach ($drivers as $driver)
    {
        if ($driver['lat'] >= $deviation_min_lat && $driver['lat'] <= $deviation_max_lat
            && $driver['lng'] >= $deviation_min_lng && $driver['lng'] <= $deviation_max_lng)
        {
            $driver_array[] = $driver;
        }
    }

    return $driver_array;

}


