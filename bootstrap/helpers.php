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
 * 辅助函数 返回距离范围内的Drivers
 * @param $drivers
 * @param $startDistance & 起始距离单位(米)
 * @param $endDistance & 结束距离单位(米)
 * @return array'
 */
function findDistanceRangeDrivers($drivers, $startDistance, $endDistance)
{
    $driver_array = array();

    foreach ($drivers as $driver)
    {
        if ($driver['distance'] >= $startDistance && $driver['distance'] <= $endDistance)
        {
            $driver_array[] = $driver;
        }
    }

    return $driver_array;
}