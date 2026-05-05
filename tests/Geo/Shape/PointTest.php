<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use PHPUnit\Framework\TestCase;

class PointTest extends TestCase
{
    public function testCreate()
    {
        $shape = new Point(1.123456, 2.23456);
        $this->assertEquals($_ENV['GEO_DEFAULT_SRID'], $shape->getSRID());

        $shape = new Point(1.123456, 2.23456, 1);
        $this->assertEquals(1, $shape->getSRID());
    }

    public function testSTGeomFromEWKT()
    {
        $point = new Point(1.0, 2.0, 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POINT(1.00000000 2.00000000)')";
        $this->assertSame($expected, $point->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTWithDecimals()
    {
        $point = new Point(12.3456, 78.9101, 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POINT(12.34560000 78.91010000)')";
        $this->assertSame($expected, $point->ST_GeomFromEWKT());
    }

    public function testGeoJSON()
    {
        $jsonData = [
            'type' => 'Point',
            'coordinates' => [30, 10]
        ];

        $point = Point::createFromGeoJSON($jsonData);

        $this->assertEquals(4326, $point->getSRID());
        $this->assertEquals($jsonData, $point->toGeoJSON());
    }

    public function testFloatingPointPrecisionEquality()
    {
        $o = ini_get('precision');
        ini_set('precision', 17);
        $point1 = new Point(0.1+0.2, 0.1+0.2, 4326);
        $point2 = new Point(0.3, 0.3, 4326);
        $this->assertTrue($point1->equals($point2));
        ini_set('precision', $o);
    }

}