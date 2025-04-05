<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape\Point;
use Milanmadar\CoolioORM\Geo\Shape\MultiLineString;
use Milanmadar\CoolioORM\Geo\Shape\LineString;
use PHPUnit\Framework\TestCase;

class MultiLineStringTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A MultiLineString with two lines
        $line1 = new LineString([
            new Point(1, 1),
            new Point(2, 2)
        ], 4326);

        $line2 = new LineString([
            new Point(3, 3),
            new Point(4, 4)
        ], 4326);

        $multiLineString = new MultiLineString([$line1, $line2], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTILINESTRING((1 1,2 2),(3 3,4 4))')";
        $this->assertSame($expected, $multiLineString->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A MultiLineString with multiple lines
        $line1 = new LineString([
            new Point(1, 1),
            new Point(2, 2),
            new Point(3, 3)
        ], 4326);

        $line2 = new LineString([
            new Point(4, 4),
            new Point(5, 5)
        ], 4326);

        $line3 = new LineString([
            new Point(6, 6),
            new Point(7, 7),
            new Point(8, 8)
        ], 4326);

        $multiLineString = new MultiLineString([$line1, $line2, $line3], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTILINESTRING((1 1,2 2,3 3),(4 4,5 5),(6 6,7 7,8 8))')";
        $this->assertSame($expected, $multiLineString->ST_GeomFromEWKT());
    }

}