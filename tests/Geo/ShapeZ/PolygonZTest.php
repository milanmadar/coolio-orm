<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\PolygonZ;
use PHPUnit\Framework\TestCase;

class PolygonZTest extends TestCase
{
    public function testCreateValid()
    {
        $line = new LineStringZ([
            new PointZ(0, 0, 1),
            new PointZ(0, 1, 2),
            new PointZ(1, 1, 3),
            new PointZ(0, 0, 1),
        ]);
        new PolygonZ([$line]);
        $this->assertEquals(1, 1);

        $samePt = new PointZ(0, 0, 1);
        $line = new LineStringZ([
            $samePt,
            new PointZ(0, 1, 2),
            new PointZ(1, 1, 3),
            $samePt,
        ]);
        new PolygonZ([$line]);
        $this->assertEquals(1, 1);
    }

    public function testCreateInvalid_NotEnoughPoints()
    {
        $this->expectException(\InvalidArgumentException::class);

        $line = new LineStringZ([
            new PointZ(0, 0, 1),
            new PointZ(1, 1, 2),
            new PointZ(0, 0, 1),
        ]);
        new PolygonZ([$line]);
    }

    public function testCreateInvalid_NotClosingPoints()
    {
        $this->expectException(\InvalidArgumentException::class);

        $line = new LineStringZ([
            new PointZ(0, 0, 2),
            new PointZ(0, 1, 2),
            new PointZ(1, 1, 2),
            new PointZ(0, 0.1, 2),
        ]);
        new PolygonZ([$line]);
    }

    public function testSTGeomFromEWKTSimple()
    {
        $outerRing = new LineStringZ([
            new PointZ(0, 0, 3),
            new PointZ(0, 1, 3),
            new PointZ(1, 1, 3),
            new PointZ(1, 0, 3),
            new PointZ(0, 0, 3),
        ]);

        $polygon = new PolygonZ([$outerRing], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POLYGON Z((0 0 3,1 0 3,1 1 3,0 1 3,0 0 3))')";
        $this->assertSame($expected, $polygon->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        $outerRing = new LineStringZ([
            new PointZ(0, 0, 4),
            new PointZ(0, 5, 4),
            new PointZ(5, 5, 4),
            new PointZ(5, 0, 4),
            new PointZ(0, 0, 4),
        ]);

        $hole = new LineStringZ([
            new PointZ(1, 1, 4),
            new PointZ(1, 2, 4),
            new PointZ(2, 2, 4),
            new PointZ(2, 1, 4),
            new PointZ(1, 1, 4),
        ]);

        $polygon = new PolygonZ([$outerRing, $hole], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POLYGON Z((0 0 4,5 0 4,5 5 4,0 5 4,0 0 4),(1 1 4,1 2 4,2 2 4,2 1 4,1 1 4))')";
        $this->assertSame($expected, $polygon->ST_GeomFromEWKT());
    }

    public function testGeoJSONPolygonZ()
    {
        $jsonData = [
            'type' => 'Polygon',
            'coordinates' => [
                [   // outer ring (CCW)
                    [0, 0, 0],
                    [10, 0, 1],
                    [10, 10, 2],
                    [0, 10, 3],
                    [0, 0, 0]
                ],
                [   // inner ring (CW)
                    [2, 2, 0],
                    [2, 5, 1],
                    [5, 5, 2],
                    [5, 2, 3],
                    [2, 2, 0]
                ]
            ]
        ];

        $polygonZ = PolygonZ::createFromGeoJSON($jsonData);

        $this->assertInstanceOf(PolygonZ::class, $polygonZ);
        $this->assertEquals(4326, $polygonZ->getSRID());

        // Validate rings
        $rings = $polygonZ->getLineStrings();
        $this->assertCount(2, $rings);

        // Outer ring
        $outer = $rings[0]->getPoints();
        $this->assertCount(5, $outer);
        $this->assertEquals([0.0, 0.0, 0.0],  $outer[0]->getCoordinates());
        $this->assertEquals([10.0, 0.0, 1.0], $outer[1]->getCoordinates());
        $this->assertEquals([10.0, 10.0, 2.0], $outer[2]->getCoordinates());
        $this->assertEquals([0.0, 10.0, 3.0],  $outer[3]->getCoordinates());
        $this->assertEquals([0.0, 0.0, 0.0],   $outer[4]->getCoordinates());

        // Inner ring
        $inner = $rings[1]->getPoints();
        $this->assertCount(5, $inner);
        $this->assertEquals([2.0, 2.0, 0.0],  $inner[0]->getCoordinates());
        $this->assertEquals([2.0, 5.0, 1.0],  $inner[1]->getCoordinates());
        $this->assertEquals([5.0, 5.0, 2.0],  $inner[2]->getCoordinates());
        $this->assertEquals([5.0, 2.0, 3.0],  $inner[3]->getCoordinates());
        $this->assertEquals([2.0, 2.0, 0.0],  $inner[4]->getCoordinates());

        // Round trip
        $this->assertEquals($jsonData, $polygonZ->toGeoJSON());
    }

}