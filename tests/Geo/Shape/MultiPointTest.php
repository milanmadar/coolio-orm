<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape\Point;
use Milanmadar\CoolioORM\Geo\Shape\MultiPoint;
use PHPUnit\Framework\TestCase;

class MultiPointTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A MultiPoint with two points
        $multiPoint = new MultiPoint([new Point(1, 1), new Point(2, 2)], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTIPOINT(1 1,2 2)')";
        $this->assertSame($expected, $multiPoint->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A MultiPoint with multiple points
        $multiPoint = new MultiPoint([
            new Point(1, 1),
            new Point(2, 2),
            new Point(3, 3),
            new Point(4, 4)
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTIPOINT(1 1,2 2,3 3,4 4)')";
        $this->assertSame($expected, $multiPoint->ST_GeomFromEWKT());
    }

}