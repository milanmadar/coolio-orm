<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\Polygon;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiPolygon;
use PHPUnit\Framework\TestCase;

class MultiPolygonTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        $multiPolygon = new MultiPolygon([
            new Polygon([
                new LineString([
                    new Point(0, 0),
                    new Point(0, 5),
                    new Point(5, 5),
                    new Point(5, 0),
                    new Point(0, 0),
                ]),
                new LineString([
                    new Point(1, 1),
                    new Point(1, 2),
                    new Point(2, 2),
                    new Point(2, 1),
                    new Point(1, 1),
                ])
            ], 4326),
            new Polygon([
                new LineString([
                    new Point(8, 8),
                    new Point(0, 5),
                    new Point(5, 5),
                    new Point(5, 0),
                    new Point(8, 8),
                ]),
                new LineString([
                    new Point(9, 9),
                    new Point(1, 2),
                    new Point(2, 2),
                    new Point(2, 1),
                    new Point(9, 9),
                ])
            ], 4326)
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTIPOLYGON(((0 0,0 5,5 5,5 0,0 0),(1 1,1 2,2 2,2 1,1 1)),((8 8,0 5,5 5,5 0,8 8),(9 9,1 2,2 2,2 1,9 9)))')";
        $this->assertSame($expected, $multiPolygon->ST_GeomFromEWKT());
    }

}