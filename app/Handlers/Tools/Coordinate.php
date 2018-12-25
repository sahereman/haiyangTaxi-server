<?php

namespace App\Handlers\Tools;


// coordinate: 坐标; latitude: 纬度; longitude: 经度.
// landscape: 横向打印的; portrait: 纵向打印的.
// vertical,perpendicular,upright,erect,plumb: 垂直的; horizontal: 水平的.
class Coordinate
{
    const EARTH_RADIUS = 6378137; // 地球半径 人类规定 (单位：m)
    const EQUATORIAL_RADIUS = 6378137; // 赤道半径 (单位：m)
    const POLAR_RADIUS = 6356725; // 极半径 (单位：m)

    public $latitudeAngular;
    public $latitudeRadian;
    public $latitudeDegree;
    public $latitudeMinute;
    public $latitudeSecond;

    public $longitudeAngular;
    public $longitudeRadian;
    public $longitudeDegree;
    public $longitudeMinute;
    public $longitudeSecond;

    public $localEarthRadius;
    public $latitudeRadius;

    // Constant M_PI is equal to pi() ...
    public function __construct($latitudeAngular, $longitudeAngular)
    {
        $this->latitudeAngular = $latitudeAngular;
        $this->latitudeRadian = $latitudeAngular * M_PI / 180;
        $this->latitudeDegree = (int)$latitudeAngular;
        $this->latitudeMinute = (int)(($latitudeAngular - $this->latitudeDegree) * 60);
        $this->latitudeSecond = ($latitudeAngular - $this->latitudeDegree - $this->latitudeMinute / 60) * 3600;

        $this->longitudeAngular = $longitudeAngular;
        $this->longitudeRadian = $longitudeAngular * M_PI / 180;
        $this->longitudeDegree = (int)$longitudeAngular;
        $this->longitudeMinute = (int)(($longitudeAngular - $this->longitudeDegree) * 60);
        $this->longitudeSecond = ($longitudeAngular - $this->longitudeDegree - $this->longitudeMinute / 60) * 3600;

        $this->localEarthRadius = self::POLAR_RADIUS + (self::EQUATORIAL_RADIUS - self::POLAR_RADIUS) * (90 - $latitudeAngular) / 90;
        $this->latitudeRadius = $this->localEarthRadius * cos($this->latitudeRadian);
    }
}