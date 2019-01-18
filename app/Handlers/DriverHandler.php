<?php

namespace App\Handlers;

use App\Handlers\Tools\Coordinate;
use App\Sockets\DriverWebSocket;
use App\Sockets\WebSocket;


/**
 * 司机数据 处理类
 * Class DriverHandler
 * @package App\Handlers
 */
class DriverHandler
{

    /**
     * 格式化RedisZset 为 Drivers数组
     * @param $zset
     * @return array
     */
    public static function getDrivers($zset)
    {
        $array = array();

        foreach ($zset as $key => $driver)
        {
            $driver = json_decode($driver, true);
            $array[] = $driver;
        }

        return $array;
    }

    /**
     * 返回闲置的Drivers
     * @param $drivers
     * @return array
     */
    public static function findFreeDrivers($drivers)
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
     * 根据距离范围返回Drivers
     * @param $drivers
     * @param $lat
     * @param $lng
     * @param int $startDistance
     * @param int $endDistance
     * @return array
     */
    public static function findDistanceRangeDrivers($drivers, $lat, $lng, $startDistance = 0, $endDistance = 1500)
    {
        $driver_array = array();
        $location_coor = new Coordinate($lat, $lng);

        foreach ($drivers as $driver)
        {
            $driver_coor = new Coordinate($driver['lat'], $driver['lng']);
            $distance = self::calcDistance($location_coor, $driver_coor);

            if ($distance >= $startDistance && $distance <= $endDistance)
            {
                $driver['distance'] = $distance;
                $driver_array[] = $driver;
            }
        }

        return $driver_array;
    }

    /**
     * 计算起始坐标 至 到达坐标 的移动方向角度 0 - 359 (正北为0 , 正南为180)
     * @param Coordinate $start_coor
     * @param Coordinate $end_coor
     * @return int|null
     */
    public static function calcAngle(Coordinate $start_coor, Coordinate $end_coor)
    {
        $dLatitudeDistance = ($end_coor->latitudeRadian - $start_coor->latitudeRadian) * $start_coor->localEarthRadius;
        $dLongitudeDistance = ($end_coor->longitudeRadian - $start_coor->longitudeRadian) * $start_coor->latitudeRadius;
        if ($dLatitudeDistance == 0)
        {
            return null;
        }

        $angle = atan(abs($dLongitudeDistance / $dLatitudeDistance)) * 180 / M_PI;
        $dLatitudeAngular = $end_coor->latitudeAngular - $start_coor->latitudeAngular;
        $dLongitudeAngular = $end_coor->longitudeAngular - $start_coor->longitudeAngular;

        if ($dLongitudeAngular > 0 && $dLatitudeAngular <= 0)
        {
            $angle = (90 - $angle) + 90;
        } else if ($dLongitudeAngular <= 0 && $dLatitudeAngular < 0)
        {
            $angle = $angle + 180;
        } else if ($dLongitudeAngular < 0 && $dLatitudeAngular >= 0)
        {
            $angle = (90 - $angle) + 270;
        }

        return intval($angle);
    }

    /**
     * 计算起始坐标 至 到达坐标 的距离 (距离单位: 米)
     * @param Coordinate $start_coor
     * @param Coordinate $end_coor
     * @return int
     */
    public static function calcDistance(Coordinate $start_coor, Coordinate $end_coor)
    {
        $dLatitudeRadian = $end_coor->latitudeRadian - $start_coor->latitudeRadian;
        $dLongitudeRadian = $end_coor->longitudeRadian - $start_coor->longitudeRadian;
        if ($dLatitudeRadian == 0)
        {
            return 0;
        }

        //google maps里面实现的算法
        $distance = 2 * asin(sqrt(pow(sin($dLatitudeRadian / 2), 2) + cos($start_coor->latitudeRadian) *
                cos($end_coor->latitudeRadian) * pow(sin($dLongitudeRadian / 2), 2))); //google maps里面实现的算法
        $distance = $distance * Coordinate::EARTH_RADIUS;

        return intval($distance);
    }


    /**
     * 以坐标差异 得到附近的车辆
     * @param $drivers
     * @param $lat
     * @param $lng
     * @param float $difference
     * @return array
     */
    public static function driversByCoordinateDifference($drivers, $lat, $lng, $difference = 0.04)
    {
        $driver_array = array();

        $deviation_min_lat = bcsub($lat, $difference, 6);
        $deviation_max_lat = bcadd($lat, $difference, 6);

        $deviation_min_lng = bcsub($lng, $difference, 6);
        $deviation_max_lng = bcadd($lng, $difference, 6);

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

}