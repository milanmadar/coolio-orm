<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\CircularStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\CompoundCurveZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\MultiCurveZ;
use PHPUnit\Framework\TestCase;

class MultiCurveZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A MultiCurve with a CircularString and a LineString as curves
        $curve1 = new CircularStringZ([new PointZ(0, 0, 4, 4326), new PointZ(1, 2, 4, 4326), new PointZ(2, 0, 4, 4326)], 4326);
        $curve2 = new LineStringZ([new PointZ(3, 3, 4, 4326), new PointZ(4, 4, 4, 4326), new PointZ(5, 5, 4, 4326)], 4326);

        $multiCurve = new MultiCurveZ([$curve1, $curve2], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTICURVEZ(CIRCULARSTRINGZ(0 0 4,1 2 4,2 0 4),LINESTRING Z(3 3 4,4 4 4,5 5 4))')";
        $this->assertSame($expected, $multiCurve->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A MultiCurve with a CircularString, CompoundCurve, and a LineString as curves
        $curve1 = new CircularStringZ([new PointZ(0, 0, 0, 4326), new PointZ(2, 0, 0, 4326), new PointZ(2, 2, 0, 4326), new PointZ(0, 2, 0, 4326), new PointZ(0, 0, 0, 4326)], 4326);
        $curve2 = new LineStringZ([new PointZ(3, 3, 0, 4326), new PointZ(5, 5, 0, 4326)], 4326);
        $curve3 = new CompoundCurveZ([new LineStringZ([new PointZ(6, 6, 0, 4326), new PointZ(7, 7, 0, 4326)]),
            new CircularStringZ([new PointZ(7, 7, 0, 4326), new PointZ(8, 8, 0, 4326), new PointZ(9, 7, 0, 4326)])], 4326);

        $multiCurve = new MultiCurveZ([$curve1, $curve2, $curve3], 4326);

        // Construct the expected EWKT for the MultiCurve
        $expected = "ST_GeomFromEWKT('SRID=4326;MULTICURVEZ(CIRCULARSTRINGZ(0 0 0,2 0 0,2 2 0,0 2 0,0 0 0),LINESTRING Z(3 3 0,5 5 0),COMPOUNDCURVEZ(LINESTRING Z(6 6 0,7 7 0),CIRCULARSTRINGZ(7 7 0,8 8 0,9 7 0)))')";
        $this->assertSame($expected, $multiCurve->ST_GeomFromEWKT());
    }

}