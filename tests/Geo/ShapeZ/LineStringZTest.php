<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use PHPUnit\Framework\TestCase;

class LineStringZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        $lineString = new LineStringZ([
            new PointZ(1,2,3),
            new PointZ(4,5,6),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;LINESTRING Z(1 2 3,4 5 6)')";
        $this->assertSame($expected, $lineString->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        $lineString = new LineStringZ([
            new PointZ(0, 0, 0),
            new PointZ(1, 2, 1),
            new PointZ(2, 4, 2),
            new PointZ(5, 6, 3),
            new PointZ(7, 8, 4),
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;LINESTRING Z(0 0 0,1 2 1,2 4 2,5 6 3,7 8 4)')";
        $this->assertSame($expected, $lineString->ST_GeomFromEWKT());
    }

    public function testGeoJSONLineStringZ()
    {
        $jsonData = [
            'type' => 'LineString',
            'coordinates' => [
                [10, 20, 5],
                [15, 25, 6],
                [20, 30, 7]
            ]
        ];

        $lineStringZ = LineStringZ::createFromGeoJSON($jsonData);

        $this->assertInstanceOf(LineStringZ::class, $lineStringZ);
        $this->assertEquals(4326, $lineStringZ->getSRID());

        // Validate points
        $points = $lineStringZ->getPoints();

        $this->assertCount(3, $points);

        $this->assertEquals([10.0, 20.0, 5.0],  $points[0]->getCoordinates());
        $this->assertEquals([15.0, 25.0, 6.0],  $points[1]->getCoordinates());
        $this->assertEquals([20.0, 30.0, 7.0],  $points[2]->getCoordinates());

        // Round trip
        $this->assertEquals($jsonData, $lineStringZ->toGeoJSON());
    }
}