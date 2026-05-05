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

        $expected = "ST_GeomFromEWKT('SRID=4326;COMPOUNDCURVE Z(LINESTRING Z(2.00000000 0.00000000 1.50000000,3.00000000 1.00000000 1.50000000),CIRCULARSTRING Z(3.00000000 1.00000000 1.50000000,4.00000000 2.00000000 1.50000000,5.00000000 1.00000000 1.50000000))')";
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

        $expected = "ST_GeomFromEWKT('SRID=4326;COMPOUNDCURVE Z(LINESTRING Z(2.00000000 0.00000000 0.00000000,3.00000000 1.00000000 0.00000000),CIRCULARSTRING Z(3.00000000 1.00000000 0.00000000,4.00000000 2.00000000 0.00000000,5.00000000 1.00000000 0.00000000),LINESTRING Z(5.00000000 1.00000000 0.00000000,6.00000000 0.00000000 0.00000000))')";
        $this->assertSame($expected, $compoundCurve->ST_GeomFromEWKT());
    }

}