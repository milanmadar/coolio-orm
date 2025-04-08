<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\CircularString;
use Milanmadar\CoolioORM\Geo\Shape2D\CompoundCurve;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiCurve;
use PHPUnit\Framework\TestCase;

class MultiCurveTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A MultiCurve with a CircularString and a LineString as curves
        $curve1 = new CircularString([new Point(0, 0, 4326), new Point(1, 2, 4326), new Point(2, 0, 4326)], 4326);
        $curve2 = new LineString([new Point(3, 3, 4326), new Point(4, 4, 4326), new Point(5, 5, 4326)], 4326);

        $multiCurve = new MultiCurve([$curve1, $curve2], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTICURVE(CIRCULARSTRING(0 0,1 2,2 0),LINESTRING(3 3,4 4,5 5))')";
        $this->assertSame($expected, $multiCurve->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A MultiCurve with a CircularString, CompoundCurve, and a LineString as curves
        $curve1 = new CircularString([new Point(0, 0, 4326), new Point(2, 0, 4326), new Point(2, 2, 4326), new Point(0, 2, 4326), new Point(0, 0, 4326)], 4326);
        $curve2 = new LineString([new Point(3, 3, 4326), new Point(5, 5, 4326)], 4326);
        $curve3 = new CompoundCurve([new LineString([new Point(6, 6, 4326), new Point(7, 7, 4326)]),
            new CircularString([new Point(7, 7, 4326), new Point(8, 8, 4326), new Point(9, 7, 4326)])], 4326);

        $multiCurve = new MultiCurve([$curve1, $curve2, $curve3], 4326);

        // Construct the expected EWKT for the MultiCurve
        $expected = "ST_GeomFromEWKT('SRID=4326;MULTICURVE(CIRCULARSTRING(0 0,2 0,2 2,0 2,0 0),LINESTRING(3 3,5 5),COMPOUNDCURVE(LINESTRING(6 6,7 7),CIRCULARSTRING(7 7,8 8,9 7)))')";
        $this->assertSame($expected, $multiCurve->ST_GeomFromEWKT());
    }

}