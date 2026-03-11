<?php

namespace Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\ShapeZM\CompoundCurveZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\CircularStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use PHPUnit\Framework\TestCase;

class CompoundCurveZMTest extends TestCase
{
    public function testCreateFromConstructor()
    {
        $line1 = new LineStringZM([
            new PointZM(0, 0, 0, 0),
            new PointZM(1, 1, 1, 1)
        ]);

        $circular = new CircularStringZM([
            new PointZM(1, 1, 1, 1),
            new PointZM(2, 0, 2, 2),
            new PointZM(3, 1, 3, 3)
        ]);

        $line2 = new LineStringZM([
            new PointZM(3, 1, 3, 3),
            new PointZM(4, 0, 4, 4)
        ]);

        $curve = new CompoundCurveZM([$line1, $circular, $line2], 4326);

        $this->assertCount(3, $curve->getSegments());
        $this->assertEquals($line1, $curve->getSegments()[0]);
        $this->assertEquals($circular, $curve->getSegments()[1]);
        $this->assertEquals($line2, $curve->getSegments()[2]);
        $this->assertEquals(4326, $curve->getSegments()[0]->getPoints()[0]->getSRID());
    }

    public function testToWKT()
    {
        $line = new LineStringZM([
            new PointZM(0,0,0,0),
            new PointZM(1,1,1,1)
        ]);

        $circular = new CircularStringZM([
            new PointZM(1,1,1,1),
            new PointZM(2,0,2,2),
            new PointZM(3,1,3,3)
        ]);

        $curve = new CompoundCurveZM([$line, $circular], 4326);

        $expected = 'COMPOUNDCURVE ZM(' . $line->toWKT() . ',' . $circular->toWKT() . ')';
        $this->assertEquals($expected, $curve->toWKT());
    }

    public function testCreateFromEWKT()
    {
        $ewkt = 'SRID=4326;COMPOUNDCURVE Z(LINESTRING ZM(0 0 0 0,1 1 1 1),CIRCULARSTRING ZM(1 1 1 1,2 0 2 2,3 1 3 3))';

        $curve = CompoundCurveZM::createFromGeoEWKTString($ewkt);

        $this->assertInstanceOf(CompoundCurveZM::class, $curve);
        $this->assertCount(2, $curve->getSegments());

        $firstSegment = $curve->getSegments()[0];
        $secondSegment = $curve->getSegments()[1];

        $this->assertInstanceOf(LineStringZM::class, $firstSegment);
        $this->assertInstanceOf(CircularStringZM::class, $secondSegment);

        $this->assertEquals(0, $firstSegment->getPoints()[0]->getX());
        $this->assertEquals(3, $secondSegment->getPoints()[2]->getX());
    }

    public function testCreateFromEWKT2()
    {
        $ewkt = 'SRID=4326;COMPOUNDCURVE((2 0 0 99,3 1 0 99),CIRCULARSTRING(3 1 0 99,4 2 0 99,5 1 0 99),(5 1 0 99,6 0 0 99))';
        $curve = CompoundCurveZM::createFromGeoEWKTString($ewkt);
        $this->assertTrue(true);
    }

    public function testSegmentContinuityThrows()
    {
        $this->expectException(\InvalidArgumentException::class);

        $line1 = new LineStringZM([
            new PointZM(0,0,0,0),
            new PointZM(1,1,1,1)
        ]);

        $line2 = new LineStringZM([
            new PointZM(2,2,2,2), // does not match line1 end
            new PointZM(3,3,3,3)
        ]);

        new CompoundCurveZM([$line1, $line2], 4326);
    }

    public function testGetStartEndPoints()
    {
        $line = new LineStringZM([
            new PointZM(0,0,0,0),
            new PointZM(1,1,1,1)
        ]);

        $circular = new CircularStringZM([
            new PointZM(1,1,1,1),
            new PointZM(2,0,2,2),
            new PointZM(3,1,3,3)
        ]);

        $curve = new CompoundCurveZM([$line, $circular], 4326);

        $this->assertEquals($line->getPoints()[0], $curve->getStartPointZM());
        $this->assertEquals($circular->getPoints()[2], $curve->getEndPointZM());
    }

    public function testEmptySegmentsThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CompoundCurveZM([], 4326);
    }
}