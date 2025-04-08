<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\CircularString;
use Milanmadar\CoolioORM\Geo\Shape2D\CompoundCurve;
use PHPUnit\Framework\TestCase;

class CompoundCurveTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A CompoundCurve with LineString and CircularString
        $compoundCurve = new CompoundCurve([
            new LineString([new Point(2, 0), new Point(3, 1)]),
            new CircularString([new Point(3, 1), new Point(4, 2), new Point(5, 1)]),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;COMPOUNDCURVE(LINESTRING(2 0,3 1),CIRCULARSTRING(3 1,4 2,5 1))')";
        $this->assertSame($expected, $compoundCurve->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A CompoundCurve with multiple LineStrings and CircularStrings
        $compoundCurve = new CompoundCurve([
            new LineString([new Point(2, 0), new Point(3, 1)]),
            new CircularString([new Point(3, 1), new Point(4, 2), new Point(5, 1)]),
            new LineString([new Point(5, 1), new Point(6, 0)]),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;COMPOUNDCURVE(LINESTRING(2 0,3 1),CIRCULARSTRING(3 1,4 2,5 1),LINESTRING(5 1,6 0))')";
        $this->assertSame($expected, $compoundCurve->ST_GeomFromEWKT());
    }

}