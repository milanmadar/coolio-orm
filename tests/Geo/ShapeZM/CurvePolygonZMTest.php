<?php

namespace Geo\ShapeZM;

use PHPUnit\Framework\TestCase;
use Milanmadar\CoolioORM\Geo\ShapeZM\CurvePolygonZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\CircularStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;

class CurvePolygonZMTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: CurvePolygon with a CircularStringZM and a LineStringZM as boundary
        $outerRing = new CircularStringZM([
            new PointZM(0, 0, 3, 100),
            new PointZM(4, 0, 3, 100),
            new PointZM(4, 4, 3, 100),
            new PointZM(0, 4, 3, 100),
            new PointZM(0, 0, 3, 100),
        ], 4326);

        $innerRing = new LineStringZM([
            new PointZM(1, 1, 3, 100),
            new PointZM(3, 1, 3, 100),
            new PointZM(3, 3, 3, 100),
            new PointZM(1, 3, 3, 100),
            new PointZM(1, 1, 3, 100),
        ], 4326);

        $curvePolygon = new CurvePolygonZM([$outerRing, $innerRing], 4326);

        $expected = "CURVEPOLYGON ZM(CIRCULARSTRING ZM(0 0 3 100,4 0 3 100,4 4 3 100,0 4 3 100,0 0 3 100),LINESTRING ZM(1 1 3 100,3 1 3 100,3 3 3 100,1 3 3 100,1 1 3 100))";

        $this->assertSame($expected, $curvePolygon->toWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: CurvePolygon with multiple inner boundaries
        $outerRing = new CircularStringZM([
            new PointZM(0, 0, 3, 50),
            new PointZM(6, 0, 3, 50),
            new PointZM(6, 6, 3, 50),
            new PointZM(0, 6, 3, 50),
            new PointZM(0, 0, 3, 50),
        ], 4326);

        $hole1 = new LineStringZM([
            new PointZM(2, 2, 3, 50),
            new PointZM(3, 2, 3, 50),
            new PointZM(3, 3, 3, 50),
            new PointZM(2, 3, 3, 50),
            new PointZM(2, 2, 3, 50),
        ], 4326);

        $hole2 = new CircularStringZM([
            new PointZM(1, 1, 3, 50),
            new PointZM(2, 1, 3, 50),
            new PointZM(2, 2, 3, 50),
            new PointZM(1, 2, 3, 50),
            new PointZM(1, 1, 3, 50),
        ], 4326);

        $curvePolygon = new CurvePolygonZM([$outerRing, $hole1, $hole2], 4326);

        $expected = "CURVEPOLYGON ZM(CIRCULARSTRING ZM(0 0 3 50,6 0 3 50,6 6 3 50,0 6 3 50,0 0 3 50),LINESTRING ZM(2 2 3 50,3 2 3 50,3 3 3 50,2 3 3 50,2 2 3 50),CIRCULARSTRING ZM(1 1 3 50,2 1 3 50,2 2 3 50,1 2 3 50,1 1 3 50))";

        $this->assertSame($expected, $curvePolygon->toWKT());
    }

    public function testCreateFromGeoEWKTString()
    {
        $ewkt = 'SRID=4326;CURVEPOLYGON ZM(CIRCULARSTRING ZM(0 0 3 10,4 0 3 10,4 4 3 10,0 4 3 10,0 0 3 10),LINESTRING ZM(1 1 3 10,3 1 3 10,3 3 3 10,1 3 3 10,1 1 3 10))';
        $curvePolygon = CurvePolygonZM::createFromGeoEWKTString($ewkt);

        $this->assertInstanceOf(CurvePolygonZM::class, $curvePolygon);
        $this->assertCount(2, $curvePolygon->getBoundaries());

        $firstBoundary = $curvePolygon->getBoundaries()[0];
        $this->assertInstanceOf(CircularStringZM::class, $firstBoundary);
        $this->assertEquals([0, 0, 3, 10], [
            $firstBoundary->getPoints()[0]->getX(),
            $firstBoundary->getPoints()[0]->getY(),
            $firstBoundary->getPoints()[0]->getZ(),
            $firstBoundary->getPoints()[0]->getM()
        ]);

        $secondBoundary = $curvePolygon->getBoundaries()[1];
        $this->assertInstanceOf(LineStringZM::class, $secondBoundary);
        $this->assertEquals([1, 1, 3, 10], [
            $secondBoundary->getPoints()[0]->getX(),
            $secondBoundary->getPoints()[0]->getY(),
            $secondBoundary->getPoints()[0]->getZ(),
            $secondBoundary->getPoints()[0]->getM()
        ]);
    }

    public function testCreateFromGeoEWKTStringSingleCircularBoundary()
    {
        $ewkt = 'SRID=4326;CURVEPOLYGON ZM(CIRCULARSTRING ZM(0 0 0 100,2 2 2 100,4 0 0 100,5 2 2 100,0 0 0 100))';
        $curvePolygon = CurvePolygonZM::createFromGeoEWKTString($ewkt);

        $this->assertInstanceOf(CurvePolygonZM::class, $curvePolygon);
        $this->assertCount(1, $curvePolygon->getBoundaries());

        $boundary = $curvePolygon->getBoundaries()[0];
        $this->assertInstanceOf(CircularStringZM::class, $boundary);

        $expectedCoords = [
            [0, 0, 0, 100],
            [2, 2, 2, 100],
            [4, 0, 0, 100],
            [5, 2, 2, 100],
            [0, 0, 0, 100],
        ];

        foreach ($boundary->getPoints() as $i => $point) {
            $this->assertEquals($expectedCoords[$i], [
                $point->getX(),
                $point->getY(),
                $point->getZ(),
                $point->getM()
            ]);
        }
    }

    public function testEmptyBoundariesThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CurvePolygonZM([]);
    }
}