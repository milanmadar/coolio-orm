<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape\Point;
use Milanmadar\CoolioORM\Geo\Shape\LineString;
use Milanmadar\CoolioORM\Geo\Shape\Polygon;
use PHPUnit\Framework\TestCase;

class PolygonTest extends TestCase
{
    public function testCreateValid()
    {
        $line = new LineString([
            new Point(0, 0),
            new Point(0, 1),
            new Point(1, 1),
            new Point(0, 0),
        ]);
        new Polygon([$line]);
        $this->assertEquals(1, 1);

        $samePt = new Point(0, 0);
        $line = new LineString([
            $samePt,
            new Point(0, 1),
            new Point(1, 1),
            $samePt,
        ]);
        new Polygon([$line]);
        $this->assertEquals(1, 1);
    }

    public function testCreateInvalid_NotEnoughPoints()
    {
        $this->expectException(\InvalidArgumentException::class);

        $line = new LineString([
            new Point(0, 0),
            new Point(1, 1),
            new Point(0, 0),
        ]);
        new Polygon([$line]);
    }

    public function testCreateInvalid_NotClosingPoints()
    {
        $this->expectException(\InvalidArgumentException::class);

        $line = new LineString([
            new Point(0, 0),
            new Point(0, 1),
            new Point(1, 1),
            new Point(0, 0.1),
        ]);
        new Polygon([$line]);
    }

    public function testSTGeomFromEWKTSimple()
    {
        $outerRing = new LineString([
            new Point(0, 0),
            new Point(0, 1),
            new Point(1, 1),
            new Point(1, 0),
            new Point(0, 0),
        ]);

        $polygon = new Polygon([$outerRing], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POLYGON((0 0,0 1,1 1,1 0,0 0))')";
        $this->assertSame($expected, $polygon->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        $outerRing = new LineString([
            new Point(0, 0),
            new Point(0, 5),
            new Point(5, 5),
            new Point(5, 0),
            new Point(0, 0),
        ]);

        $hole = new LineString([
            new Point(1, 1),
            new Point(1, 2),
            new Point(2, 2),
            new Point(2, 1),
            new Point(1, 1),
        ]);

        $polygon = new Polygon([$outerRing, $hole], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POLYGON((0 0,0 5,5 5,5 0,0 0),(1 1,1 2,2 2,2 1,1 1))')";
        $this->assertSame($expected, $polygon->ST_GeomFromEWKT());
    }

}