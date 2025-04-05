<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape\Point;
use Milanmadar\CoolioORM\Geo\Shape\LineString;
use Milanmadar\CoolioORM\Geo\Shape\Polygon;
use Milanmadar\CoolioORM\Geo\Shape\GeometryCollection;
use PHPUnit\Framework\TestCase;

class GeometryCollectionTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A GeometryCollection with a Point and a LineString
        $point = new Point(1, 1, 4326);
        $lineString = new LineString([
            new Point(2, 2, 4326),
            new Point(3, 3, 4326),
            new Point(4, 4, 4326)
        ], 4326);

        $geometryCollection = new GeometryCollection([$point, $lineString], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTION(POINT(1 1),LINESTRING(2 2,3 3,4 4))')";
        $this->assertSame($expected, $geometryCollection->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A GeometryCollection with a Point, LineString, and Polygon
        $point = new Point(1, 1, 4326);
        $lineString = new LineString([
            new Point(2, 2, 4326),
            new Point(3, 3, 4326),
            new Point(4, 4, 4326)
        ], 4326);

        // Create Polygon with outer ring and a hole
        $outerRing = new LineString([
            new Point(0, 0, 4326),
            new Point(0, 5, 4326),
            new Point(5, 5, 4326),
            new Point(5, 0, 4326),
            new Point(0, 0, 4326),
        ], 4326);

        $hole = new LineString([
            new Point(1, 1, 4326),
            new Point(1, 2, 4326),
            new Point(2, 2, 4326),
            new Point(2, 1, 4326),
            new Point(1, 1, 4326),
        ], 4326);

        $polygon = new Polygon([$outerRing, $hole], 4326);

        // Create the GeometryCollection with Point, LineString, and Polygon
        $geometryCollection = new GeometryCollection([$point, $lineString, $polygon], 4326);

        // Construct the expected EWKT for the GeometryCollection
        $expected = "ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTION(POINT(1 1),LINESTRING(2 2,3 3,4 4),POLYGON((0 0,0 5,5 5,5 0,0 0),(1 1,1 2,2 2,2 1,1 1)))')";
        $this->assertSame($expected, $geometryCollection->ST_GeomFromEWKT());
    }

}