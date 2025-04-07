<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\CircularStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\CompoundCurveZ;
use PHPUnit\Framework\TestCase;

class CompoundCurveZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A CompoundCurve with LineString and CircularString
        $compoundCurve = new CompoundCurveZ([
            new LineStringZ([new PointZ(2, 0, 1.5), new PointZ(3, 1, 1.5)]),
            new CircularStringZ([new PointZ(3, 1, 1.5), new PointZ(4, 2, 1.5), new PointZ(5, 1, 1.5)]),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;COMPOUNDCURVEZ(LINESTRING Z(2 0 1.5,3 1 1.5),CIRCULARSTRINGZ(3 1 1.5,4 2 1.5,5 1 1.5))')";
        $this->assertSame($expected, $compoundCurve->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A CompoundCurve with multiple LineStrings and CircularStrings
        $compoundCurve = new CompoundCurveZ([
            new LineStringZ([new PointZ(2, 0, 0), new PointZ(3, 1, 0)]),
            new CircularStringZ([new PointZ(3, 1, 0), new PointZ(4, 2, 0), new PointZ(5, 1, 0)]),
            new LineStringZ([new PointZ(5, 1, 0), new PointZ(6, 0, 0)]),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;COMPOUNDCURVEZ(LINESTRING Z(2 0 0,3 1 0),CIRCULARSTRINGZ(3 1 0,4 2 0,5 1 0),LINESTRING Z(5 1 0,6 0 0))')";
        $this->assertSame($expected, $compoundCurve->ST_GeomFromEWKT());
    }

}