<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\PolygonZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\GeometryCollectionZ;
use PHPUnit\Framework\TestCase;

class GeometryCollectionZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A GeometryCollection with a Point and a LineString
        $point = new PointZ(1, 1, 1,4326);
        $lineString = new LineStringZ([
            new PointZ(2, 2, 1, 4326),
            new PointZ(3, 3, 1, 4326),
            new PointZ(4, 4, 1, 4326)
        ], 4326);

        $geometryCollection = new GeometryCollectionZ([$point, $lineString], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTIONZ(POINTZ(1 1 1),LINESTRING Z(2 2 1,3 3 1,4 4 1))')";
        $this->assertSame($expected, $geometryCollection->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A GeometryCollection with a Point, LineString, and Polygon
        $point = new PointZ(1, 1, 1, 4326);
        $lineString = new LineStringZ([
            new PointZ(2, 2, 1, 4326),
            new PointZ(3, 3, 1, 4326),
            new PointZ(4, 4, 1, 4326)
        ], 4326);

        // Create Polygon with outer ring and a hole
        $outerRing = new LineStringZ([
            new PointZ(0, 0, 1, 4326),
            new PointZ(0, 5, 1, 4326),
            new PointZ(5, 5, 1, 4326),
            new PointZ(5, 0, 1, 4326),
            new PointZ(0, 0, 1, 4326),
        ], 4326);

        $hole = new LineStringZ([
            new PointZ(1, 1, 1, 4326),
            new PointZ(1, 2, 1, 4326),
            new PointZ(2, 2, 1, 4326),
            new PointZ(2, 1, 1, 4326),
            new PointZ(1, 1, 1, 4326),
        ], 4326);

        $polygon = new PolygonZ([$outerRing, $hole], 4326);

        // Create the GeometryCollection with Point, LineString, and Polygon
        $geometryCollection = new GeometryCollectionZ([$point, $lineString, $polygon], 4326);

        // Construct the expected EWKT for the GeometryCollection
        $expected = "ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTIONZ(POINTZ(1 1 1),LINESTRING Z(2 2 1,3 3 1,4 4 1),POLYGON Z((0 0 1,0 5 1,5 5 1,5 0 1,0 0 1),(1 1 1,1 2 1,2 2 1,2 1 1,1 1 1)))')";
        $this->assertSame($expected, $geometryCollection->ST_GeomFromEWKT());
    }

}