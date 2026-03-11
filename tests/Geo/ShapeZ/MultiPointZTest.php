<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\MultiPointZ;
use PHPUnit\Framework\TestCase;

class MultiPointZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A MultiPoint with two points
        $multiPoint = new MultiPointZ([new PointZ(1, 1, 1), new PointZ(2, 2, 1)], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTIPOINT Z(1 1 1,2 2 1)')";
        $this->assertSame($expected, $multiPoint->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A MultiPoint with multiple points
        $multiPoint = new MultiPointZ([
            new PointZ(1, 1, 1),
            new PointZ(2, 2, 2),
            new PointZ(3, 3, 3),
            new PointZ(4, 4, 4)
        ], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTIPOINT Z(1 1 1,2 2 2,3 3 3,4 4 4)')";
        $this->assertSame($expected, $multiPoint->ST_GeomFromEWKT());
    }

    public function testGeoJSONMultiPointZ()
    {
        $jsonData = [
            'type' => 'MultiPoint',
            'coordinates' => [
                [10, 20, 5],
                [30, 40, 6],
                [50, 60, 7]
            ]
        ];

        $multiPointZ = MultiPointZ::createFromGeoJSON($jsonData);

        $this->assertInstanceOf(MultiPointZ::class, $multiPointZ);
        $this->assertEquals(4326, $multiPointZ->getSRID());

        // Validate points
        $points = $multiPointZ->getPoints();
        $this->assertCount(3, $points);

        $this->assertEquals([10.0, 20.0, 5.0], $points[0]->getCoordinates());
        $this->assertEquals([30.0, 40.0, 6.0], $points[1]->getCoordinates());
        $this->assertEquals([50.0, 60.0, 7.0], $points[2]->getCoordinates());

        // Round trip
        $this->assertEquals($jsonData, $multiPointZ->toGeoJSON());
    }

}