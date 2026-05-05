<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\CircularStringZ;
use PHPUnit\Framework\TestCase;

class CircularStringZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        $circularString = new CircularStringZ([
            new PointZ(0, 0, 1),
            new PointZ(1, 1, 1),
            new PointZ(2, 0, 2),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;CIRCULARSTRING Z(0.00000000 0.00000000 1.00000000,1.00000000 1.00000000 1.00000000,2.00000000 0.00000000 2.00000000)')";
        $this->assertSame($expected, $circularString->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        $circularString = new CircularStringZ([
            new PointZ(0, 0, 1),
            new PointZ(4, 0, 1),
            new PointZ(4, 4, 1),
            new PointZ(0, 4, 1),
            new PointZ(0, 0, 1),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;CIRCULARSTRING Z(0.00000000 0.00000000 1.00000000,4.00000000 0.00000000 1.00000000,4.00000000 4.00000000 1.00000000,0.00000000 4.00000000 1.00000000,0.00000000 0.00000000 1.00000000)')";
        $this->assertSame($expected, $circularString->ST_GeomFromEWKT());
    }

}