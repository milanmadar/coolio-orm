<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use PHPUnit\Framework\TestCase;

class LineStringTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        $lineString = new LineString([
            new Point(0, 0),
            new Point(1, 1),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;LINESTRING(0.00000000 0.00000000,1.00000000 1.00000000)')";
        $this->assertSame($expected, $lineString->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        $lineString = new LineString([
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 4),
            new Point(5, 6),
            new Point(7, 8),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;LINESTRING(0.00000000 0.00000000,1.00000000 2.00000000,2.00000000 4.00000000,5.00000000 6.00000000,7.00000000 8.00000000)')";
        $this->assertSame($expected, $lineString->ST_GeomFromEWKT());
    }

    public function testGeoJSON()
    {
        $jsonData = [
            'type' => 'LineString',
            'coordinates' => [
                [30, 10],
                [40, 40],
                [20, 40],
                [10, 20]
            ]
        ];

        $lineString = LineString::createFromGeoJSON($jsonData);

        $this->assertEquals(4326, $lineString->getSRID());
        $this->assertEquals($jsonData, $lineString->toGeoJSON());
    }

}