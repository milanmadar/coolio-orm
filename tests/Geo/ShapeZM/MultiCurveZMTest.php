<?php

namespace Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\ShapeZM\CircularStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\CompoundCurveZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\MultiCurveZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use PHPUnit\Framework\TestCase;

class MultiCurveZMTest extends TestCase
{
    public function testCreateFromConstructor()
    {
        $line = new LineStringZM([
            new PointZM(0, 0, 0, 100),
            new PointZM(1, 1, 1, 100),
        ]);

        $circle = new CircularStringZM([
            new PointZM(1, 1, 1, 100),
            new PointZM(2, 0, 2, 100),
            new PointZM(3, 1, 3, 100),
        ]);

        $compound = new CompoundCurveZM([$line, $circle]);

        $multiCurve = new MultiCurveZM([$line, $circle, $compound]);

        $this->assertSame([$line, $circle, $compound], $multiCurve->getCurves());
    }

    public function testToWKT()
    {
        $line = new LineStringZM([
            new PointZM(0, 0, 0, 100),
            new PointZM(1, 1, 1, 100),
        ]);

        $circle = new CircularStringZM([
            new PointZM(1, 1, 1, 100),
            new PointZM(2, 0, 2, 100),
            new PointZM(3, 1, 3, 100),
        ]);

        $compound = new CompoundCurveZM([$line, $circle]);

        $multiCurve = new MultiCurveZM([$line, $circle, $compound]);

        $expected = sprintf(
            'MULTICURVE ZM(%s,%s,%s)',
            $line->toWKT(),
            $circle->toWKT(),
            $compound->toWKT()
        );

        $this->assertSame($expected, $multiCurve->toWKT());
    }

    public function testCreateFromEWKT()
    {
        $ewkt = 'SRID=4326;MULTICURVE ZM('
            . 'LINESTRING ZM(0 0 0 100,1 1 1 100),'
            . 'CIRCULARSTRING ZM(1 1 1 100,2 0 2 100,3 1 3 100),'
            . 'COMPOUNDCURVE ZM(LINESTRING ZM(0 0 0 100,1 1 1 100),CIRCULARSTRING ZM(1 1 1 100,2 0 2 100,3 1 3 100))'
            . ')';

        $multiCurve = MultiCurveZM::createFromGeoEWKTString($ewkt);

        $this->assertInstanceOf(MultiCurveZM::class, $multiCurve);
        $this->assertCount(3, $multiCurve->getCurves());

        $this->assertInstanceOf(LineStringZM::class, $multiCurve->getCurves()[0]);
        $this->assertInstanceOf(CircularStringZM::class, $multiCurve->getCurves()[1]);
        $this->assertInstanceOf(CompoundCurveZM::class, $multiCurve->getCurves()[2]);

        // Verify first point of first LineString
        $line = $multiCurve->getCurves()[0];
        $this->assertEquals(0, $line->getPoints()[0]->getX());
        $this->assertEquals(100, $line->getPoints()[0]->getM());
    }

    public function testEmptyCurvesThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MultiCurveZM([]);
    }

    public function testInvalidEWKTThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        MultiCurveZM::createFromGeoEWKTString('SRID=4326;MULTICURVE ZM(FOO(1 2 3 4))');
    }
}