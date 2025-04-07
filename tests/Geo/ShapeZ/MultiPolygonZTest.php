<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\PolygonZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\MultiPolygonZ;
use PHPUnit\Framework\TestCase;

class MultiPolygonZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        $multiPolygon = new MultiPolygonZ([
            new PolygonZ([
                new LineStringZ([
                    new PointZ(0, 0, 1),
                    new PointZ(0, 5, 1),
                    new PointZ(5, 5, 1),
                    new PointZ(5, 0, 1),
                    new PointZ(0, 0, 1),
                ]),
                new LineStringZ([
                    new PointZ(1, 1, 2),
                    new PointZ(1, 2, 2),
                    new PointZ(2, 2, 2),
                    new PointZ(2, 1, 2),
                    new PointZ(1, 1, 2),
                ])
            ], 4326),
            new PolygonZ([
                new LineStringZ([
                    new PointZ(8, 8, 3),
                    new PointZ(0, 5, 4),
                    new PointZ(5, 5, 5),
                    new PointZ(5, 0, 6),
                    new PointZ(8, 8, 3),
                ]),
                new LineStringZ([
                    new PointZ(9, 9, 2),
                    new PointZ(1, 2, 2),
                    new PointZ(2, 2, 2),
                    new PointZ(2, 1, 2),
                    new PointZ(9, 9, 2),
                ])
            ], 4326)
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTIPOLYGONZ(((0 0 1,0 5 1,5 5 1,5 0 1,0 0 1),(1 1 2,1 2 2,2 2 2,2 1 2,1 1 2)),((8 8 3,0 5 4,5 5 5,5 0 6,8 8 3),(9 9 2,1 2 2,2 2 2,2 1 2,9 9 2)))')";
        $this->assertSame($expected, $multiPolygon->ST_GeomFromEWKT());
    }

}