<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\CircularString;
use Milanmadar\CoolioORM\Geo\Shape2D\CurvePolygon;
use PHPUnit\Framework\TestCase;

class CurvePolygonTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A CurvePolygon with a CircularString and a LineString as boundary
        $outerRing = new CircularString([new Point(0, 0, 4326), new Point(4, 0, 4326), new Point(4, 4, 4326), new Point(0, 4, 4326), new Point(0, 0, 4326)], 4326);
        $innerRing = new LineString([new Point(1, 1, 4326), new Point(3, 1, 4326), new Point(3, 3, 4326), new Point(1, 3, 4326), new Point(1, 1, 4326)], 4326);

        $curvePolygon = new CurvePolygon([$outerRing, $innerRing], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;CURVEPOLYGON(CIRCULARSTRING(0 0,4 0,4 4,0 4,0 0),LINESTRING(1 1,3 1,3 3,1 3,1 1))')";
        $this->assertSame($expected, $curvePolygon->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A CurvePolygon with CircularString as outer boundary and another CircularString and LineString as inner boundaries
        $outerRing = new CircularString([new Point(0, 0, 4326), new Point(6, 0, 4326), new Point(6, 6, 4326), new Point(0, 6, 4326), new Point(0, 0, 4326)], 4326);

        $hole1 = new LineString([new Point(2, 2, 4326), new Point(3, 2, 4326), new Point(3, 3, 4326), new Point(2, 3, 4326), new Point(2, 2, 4326)], 4326);
        $hole2 = new CircularString([new Point(1, 1, 4326), new Point(2, 1, 4326), new Point(2, 2, 4326), new Point(1, 2, 4326), new Point(1, 1, 4326)], 4326);

        // Create the CurvePolygon with the outer and inner boundaries
        $curvePolygon = new CurvePolygon([$outerRing, $hole1, $hole2], 4326);

        // Construct the expected EWKT for the CurvePolygon
        $expected = "ST_GeomFromEWKT('SRID=4326;CURVEPOLYGON(CIRCULARSTRING(0 0,6 0,6 6,0 6,0 0),LINESTRING(2 2,3 2,3 3,2 3,2 2),CIRCULARSTRING(1 1,2 1,2 2,1 2,1 1))')";
        $this->assertSame($expected, $curvePolygon->ST_GeomFromEWKT());
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
        new CurvePolygon([$line]);
    }

}