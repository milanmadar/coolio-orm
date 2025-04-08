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

        $expected = "ST_GeomFromEWKT('SRID=4326;LINESTRING(0 0,1 1)')";
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

        $expected = "ST_GeomFromEWKT('SRID=4326;LINESTRING(0 0,1 2,2 4,5 6,7 8)')";
        $this->assertSame($expected, $lineString->ST_GeomFromEWKT());
    }

}