<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\Polygon;
use PHPUnit\Framework\TestCase;

class PolygonTest extends TestCase
{
    public function testCreateValid()
    {
        $line = new LineString([
            new Point(0, 0),
            new Point(0, 1),
            new Point(1, 1),
            new Point(0, 0),
        ]);
        new Polygon([$line]);
        $this->assertEquals(1, 1);

        $samePt = new Point(0, 0);
        $line = new LineString([
            $samePt,
            new Point(0, 1),
            new Point(1, 1),
            $samePt,
        ]);
        new Polygon([$line]);
        $this->assertEquals(1, 1);
    }

    public function testCreateInvalid_NotEnoughPoints()
    {
        $this->expectException(\InvalidArgumentException::class);

        $line = new LineString([
            new Point(0, 0),
            new Point(1, 1),
            new Point(0, 0),
        ]);
        new Polygon([$line]);
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
        new Polygon([$line]);
    }

    public function testSTGeomFromEWKTSimple()
    {
        $outerRing = new LineString([
            new Point(0, 0),
            new Point(0, 1),
            new Point(1, 1),
            new Point(1, 0),
            new Point(0, 0),
        ]);

        $polygon = new Polygon([$outerRing], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POLYGON((0 0,1 0,1 1,0 1,0 0))')";
        $this->assertSame($expected, $polygon->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        $outerRing = new LineString([
            new Point(0, 0),
            new Point(0, 5),
            new Point(5, 5),
            new Point(5, 0),
            new Point(0, 0),
        ]);

        $hole = new LineString([
            new Point(1, 1),
            new Point(1, 2),
            new Point(2, 2),
            new Point(2, 1),
            new Point(1, 1),
        ]);

        $polygon = new Polygon([$outerRing, $hole], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POLYGON((0 0,5 0,5 5,0 5,0 0),(1 1,1 2,2 2,2 1,1 1))')";
        $this->assertSame($expected, $polygon->ST_GeomFromEWKT());
    }

    public function testGeoJSONPolygon()
    {
        $jsonData = [
            'type' => 'Polygon',
            'coordinates' => [
                [   // outer ring
                    [30, 10],
                    [40, 40],
                    [20, 40],
                    [10, 20],
                    [30, 10]
                ],
                [   // inner ring (hole)
                    [20, 30],
                    [35, 35],
                    [30, 20],
                    [20, 30]
                ]
            ]
        ];

        $polygon = Polygon::createFromGeoJSON($jsonData);

        $this->assertEquals(4326, $polygon->getSRID());
        $this->assertEquals($jsonData, $polygon->toGeoJSON());
    }

    public function testPolygonWindingFix()
    {
        $srid = 4326;

        // Outer ring (incorrectly clockwise)
        $outerPoints = [
            new Point(0,0,$srid),
            new Point(0,10,$srid),
            new Point(10,10,$srid),
            new Point(10,0,$srid),
            new Point(0,0,$srid),
        ];

        // Inner ring (hole) (incorrectly counter-clockwise)
        $innerPoints = [
            new Point(2,2,$srid),
            new Point(8,2,$srid),
            new Point(8,8,$srid),
            new Point(2,8,$srid),
            new Point(2,2,$srid),
        ];

        $outerLine = new LineString($outerPoints, $srid);
        $innerLine = new LineString($innerPoints, $srid);

        $polygon = new Polygon([$outerLine, $innerLine], $srid);

        $outerAfter = $polygon->getLineStrings()[0]->getPoints();
        $innerAfter = $polygon->getLineStrings()[1]->getPoints();

        // The outer ring should now be counter-clockwise
        $this->assertTrue($this->_callPrivateIsCCW($outerAfter));

        // The inner ring should now be clockwise
        $this->assertFalse($this->_callPrivateIsCCW($innerAfter));
    }

    public function testCreatePolygonFromEWKTComplex()
    {
        $ewkt = 'SRID=4326;POLYGON((0 0,0 3,3 3,3 0,0 0),(4 4,4 6,6 6,6 4,4 4))';

        $polygon = Polygon::createFromGeoEWKTString($ewkt);

        // Check outer ring
        $outer = $polygon->getLineStrings()[0]->getPoints();
        $this->assertEquals([
            [0,0],
            [3,0],
            [3,3],
            [0,3],
            [0,0]
        ], array_map(fn(Point $p) => [$p->getX(), $p->getY()], $outer));

        // Check inner ring (hole)
        $inner = $polygon->getLineStrings()[1]->getPoints();
        $this->assertEquals([
            [4,4],
            [4,6],
            [6,6],
            [6,4],
            [4,4]
        ], array_map(fn(Point $p) => [$p->getX(), $p->getY()], $inner));
    }

    /**
     * Tests the centroid of a simple unit square.
     * This bypasses any resort logic and tests the math of the Polygon class directly.
     */
    public function testGetCenterPointWithUnitSquare(): void
    {
        // 1. Define a 1x1 square
        // The Polygon constructor will handle the winding order (CCW)
        // because of your _validateRings logic.
        $points = [
            new Point(0.0, 0.0),
            new Point(1.0, 0.0),
            new Point(1.0, 1.0),
            new Point(0.0, 1.0),
            new Point(0.0, 0.0),
        ];

        $outerRing = new LineString($points);
        $polygon = new Polygon([$outerRing]);

        // 2. Calculate the center
        $center = $polygon->getCenterPoint();

        // 3. Assertions
        // For a square with corners (0,0) and (1,1), the center is (0.5, 0.5)
        $this->assertEquals(0.5, $center->getX(), 'The X coordinate of the centroid should be 0.5');
        $this->assertEquals(0.5, $center->getY(), 'The Y coordinate of the centroid should be 0.5');
    }

    /**
     * Tests a Right Triangle
     * Centroid of a triangle is (x1+x2+x3)/3, (y1+y2+y3)/3
     */
    public function testGetCenterPointWithTriangle(): void
    {
        $points = [
            new Point(0.0, 0.0),
            new Point(3.0, 0.0),
            new Point(0.0, 3.0),
            new Point(0.0, 0.0),
        ];

        $polygon = new Polygon([new LineString($points)]);
        $center = $polygon->getCenterPoint();

        // (0+3+0)/3 = 1.0
        $this->assertEquals(1.0, $center->getX());
        $this->assertEquals(1.0, $center->getY());
    }

    /**
     * Test the fallback for a degenerate polygon (zero area)
     */
    public function testGetCenterPointWithDegeneratePolygon(): void
    {
        // A "polygon" where all points are on a single line (Area = 0)
        $points = [
            new Point(0.0, 0.0),
            new Point(2.0, 0.0),
            new Point(1.0, 0.0),
            new Point(0.0, 0.0),
        ];

        $polygon = new Polygon([new LineString($points)]);
        $center = $polygon->getCenterPoint();

        // Should fallback to bounding box center: (minX+maxX)/2 = (0+2)/2 = 1.0
        $this->assertEquals(1.0, $center->getX());
        $this->assertEquals(0.0, $center->getY());
    }

    /**
     * Helper to call the private _isCCW method of Polygon.
     */
    private function _callPrivateIsCCW(array $points): bool
    {
        $polygon = new Polygon([new LineString(array_merge($points, [$points[0]]))]); // dummy Polygon
        $reflection = new \ReflectionClass($polygon);
        $method = $reflection->getMethod('_isCCW');
        $method->setAccessible(true);
        return $method->invoke($polygon, $points);
    }

}