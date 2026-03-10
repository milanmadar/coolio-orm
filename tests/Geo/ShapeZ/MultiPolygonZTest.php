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

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTIPOLYGON Z(((0 0 1,5 0 1,5 5 1,0 5 1,0 0 1),(1 1 2,1 2 2,2 2 2,2 1 2,1 1 2)),((8 8 3,0 5 4,5 5 5,5 0 6,8 8 3),(9 9 2,2 1 2,2 2 2,1 2 2,9 9 2)))')";
        $this->assertSame($expected, $multiPolygon->ST_GeomFromEWKT());
    }

    public function testGeoJSONMultiPolygonZ()
    {
        $jsonData = [
            'type' => 'MultiPolygon',
            'coordinates' => [

                // ---------- FIRST POLYGON (no holes) ----------
                [
                    [   // outer ring CCW
                        [0, 0, 0],
                        [10, 0, 1],
                        [10, 10, 2],
                        [0, 10, 3],
                        [0, 0, 0]
                    ]
                ],

                // ---------- SECOND POLYGON (with one hole) ----------
                [
                    [   // outer ring CCW
                        [20, 20, 0],
                        [30, 20, 1],
                        [30, 30, 2],
                        [20, 30, 3],
                        [20, 20, 0]
                    ],
                    [   // inner ring CW
                        [22, 22, 0],
                        [22, 26, 1],
                        [26, 26, 2],
                        [26, 22, 3],
                        [22, 22, 0]
                    ]
                ]
            ]
        ];

        $multiPolygonZ = MultiPolygonZ::createFromGeoJSON($jsonData);

        $this->assertInstanceOf(MultiPolygonZ::class, $multiPolygonZ);
        $this->assertEquals(4326, $multiPolygonZ->getSRID());

        $polygons = $multiPolygonZ->getPolygons();
        $this->assertCount(2, $polygons);

        // ---------- Validate first polygon ----------
        $poly1 = $polygons[0]->getLineStrings();
        $this->assertCount(1, $poly1);

        $outer1 = $poly1[0]->getPoints();
        $this->assertEquals([0.0, 0.0, 0.0], $outer1[0]->getCoordinates());
        $this->assertEquals([10.0, 0.0, 1.0], $outer1[1]->getCoordinates());
        $this->assertEquals([10.0, 10.0, 2.0], $outer1[2]->getCoordinates());
        $this->assertEquals([0.0, 10.0, 3.0], $outer1[3]->getCoordinates());
        $this->assertEquals([0.0, 0.0, 0.0], $outer1[4]->getCoordinates());

        // ---------- Validate second polygon ----------
        $poly2 = $polygons[1]->getLineStrings();
        $this->assertCount(2, $poly2);

        // outer ring
        $outer2 = $poly2[0]->getPoints();
        $this->assertEquals([20.0, 20.0, 0.0], $outer2[0]->getCoordinates());
        $this->assertEquals([30.0, 20.0, 1.0], $outer2[1]->getCoordinates());
        $this->assertEquals([30.0, 30.0, 2.0], $outer2[2]->getCoordinates());
        $this->assertEquals([20.0, 30.0, 3.0], $outer2[3]->getCoordinates());
        $this->assertEquals([20.0, 20.0, 0.0], $outer2[4]->getCoordinates());

        // inner ring (hole)
        $inner2 = $poly2[1]->getPoints();
        $this->assertEquals([22.0, 22.0, 0.0], $inner2[0]->getCoordinates());
        $this->assertEquals([22.0, 26.0, 1.0], $inner2[1]->getCoordinates());
        $this->assertEquals([26.0, 26.0, 2.0], $inner2[2]->getCoordinates());
        $this->assertEquals([26.0, 22.0, 3.0], $inner2[3]->getCoordinates());
        $this->assertEquals([22.0, 22.0, 0.0], $inner2[4]->getCoordinates());

        // ---------- Round trip ----------
        $this->assertEquals($jsonData, $multiPolygonZ->toGeoJSON());
    }

}