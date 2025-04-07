<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\CircularStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\CurvePolygonZ;
use PHPUnit\Framework\TestCase;

class CurvePolygonZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A CurvePolygon with a CircularString and a LineString as boundary
        $outerRing = new CircularStringZ([new PointZ(0, 0, 3, 4326), new PointZ(4, 0, 3, 4326), new PointZ(4, 4, 3, 4326), new PointZ(0, 4, 3, 4326), new PointZ(0, 0, 3, 4326)], 4326);
        $innerRing = new LineStringZ([new PointZ(1, 1, 3, 4326), new PointZ(3, 1, 3, 4326), new PointZ(3, 3, 3, 4326), new PointZ(1, 3, 3, 4326), new PointZ(1, 1, 3, 4326)], 4326);

        $curvePolygon = new CurvePolygonZ([$outerRing, $innerRing], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;CURVEPOLYGONZ(CIRCULARSTRINGZ(0 0 3,4 0 3,4 4 3,0 4 3,0 0 3),LINESTRING Z(1 1 3,3 1 3,3 3 3,1 3 3,1 1 3))')";
        $this->assertSame($expected, $curvePolygon->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A CurvePolygon with CircularString as outer boundary and another CircularString and LineString as inner boundaries
        $outerRing = new CircularStringZ([new PointZ(0, 0, 3, 4326), new PointZ(6, 0, 3, 4326), new PointZ(6, 6, 3, 4326), new PointZ(0, 6, 3, 4326), new PointZ(0, 0, 3, 4326)], 4326);

        $hole1 = new LineStringZ([new PointZ(2, 2, 3, 4326), new PointZ(3, 2, 3, 4326), new PointZ(3, 3, 3, 4326), new PointZ(2, 3, 3, 4326), new PointZ(2, 2, 3, 4326)], 4326);
        $hole2 = new CircularStringZ([new PointZ(1, 1, 3, 4326), new PointZ(2, 1, 3, 4326), new PointZ(2, 2, 3, 4326), new PointZ(1, 2, 3, 4326), new PointZ(1, 1, 3, 4326)], 4326);

        // Create the CurvePolygon with the outer and inner boundaries
        $curvePolygon = new CurvePolygonZ([$outerRing, $hole1, $hole2], 4326);

        // Construct the expected EWKT for the CurvePolygon
        $expected = "ST_GeomFromEWKT('SRID=4326;CURVEPOLYGONZ(CIRCULARSTRINGZ(0 0 3,6 0 3,6 6 3,0 6 3,0 0 3),LINESTRING Z(2 2 3,3 2 3,3 3 3,2 3 3,2 2 3),CIRCULARSTRINGZ(1 1 3,2 1 3,2 2 3,1 2 3,1 1 3))')";
        $this->assertSame($expected, $curvePolygon->ST_GeomFromEWKT());
    }

}