<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use PHPUnit\Framework\TestCase;

class LineStringZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        $lineString = new LineStringZ([
            new PointZ(1,2,3),
            new PointZ(4,5,6),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;LINESTRING Z(1 2 3,4 5 6)')";
        $this->assertSame($expected, $lineString->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        $lineString = new LineStringZ([
            new PointZ(0, 0, 0),
            new PointZ(1, 2, 1),
            new PointZ(2, 4, 2),
            new PointZ(5, 6, 3),
            new PointZ(7, 8, 4),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;LINESTRING Z(0 0 0,1 2 1,2 4 2,5 6 3,7 8 4)')";
        $this->assertSame($expected, $lineString->ST_GeomFromEWKT());
    }

}