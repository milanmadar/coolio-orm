<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\CircularString;
use PHPUnit\Framework\TestCase;

class CircularStringTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        $circularString = new CircularString([
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;CIRCULARSTRING(0.00000000 0.00000000,1.00000000 1.00000000,2.00000000 0.00000000)')";
        $this->assertSame($expected, $circularString->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        $circularString = new CircularString([
            new Point(0, 0),
            new Point(4, 0),
            new Point(4, 4),
            new Point(0, 4),
            new Point(0, 0),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;CIRCULARSTRING(0.00000000 0.00000000,4.00000000 0.00000000,4.00000000 4.00000000,0.00000000 4.00000000,0.00000000 0.00000000)')";
        $this->assertSame($expected, $circularString->ST_GeomFromEWKT());
    }

}