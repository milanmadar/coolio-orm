<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\MultiPointZ;
use PHPUnit\Framework\TestCase;

class MultiPointZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A MultiPoint with two points
        $multiPoint = new MultiPointZ([new PointZ(1, 1, 1), new PointZ(2, 2, 1)], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTIPOINT Z(1 1 1,2 2 1)')";
        $this->assertSame($expected, $multiPoint->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A MultiPoint with multiple points
        $multiPoint = new MultiPointZ([
            new PointZ(1, 1, 1),
            new PointZ(2, 2, 2),
            new PointZ(3, 3, 3),
            new PointZ(4, 4, 4)
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTIPOINT Z(1 1 1,2 2 2,3 3 3,4 4 4)')";
        $this->assertSame($expected, $multiPoint->ST_GeomFromEWKT());
    }

}