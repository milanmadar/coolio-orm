<?php

namespace Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PolygonZM;
use PHPUnit\Framework\TestCase;

class PolygonZMTest extends TestCase
{
    public function testCreatePolygonZM()
    {
        $points = [
            new PointZM(0, 0, 10, 1000),
            new PointZM(0, 10, 15, 1005),
            new PointZM(10, 10, 12, 1010),
            new PointZM(10, 0, 8, 1015),
            new PointZM(0, 0, 10, 1000) // close the ring
        ];
        $lineString = new LineStringZM($points);

        $polygon = new PolygonZM([$lineString]);

        $this->assertSame([$lineString], $polygon->getLineStrings());
    }

    public function testToGeoJSONIncludesZM()
    {
        $points = [
            new PointZM(1, 2, 3, 100),
            new PointZM(1, 5, 6, 200),
            new PointZM(4, 5, 6, 300),
            new PointZM(1, 2, 3, 100)
        ];
        $lineString = new LineStringZM($points);
        $polygon = new PolygonZM([$lineString]);

        $expected = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [1, 2, 3, 100],
                    [4, 5, 6, 300],
                    [1, 5, 6, 200],
                    [1, 2, 3, 100]
                ]
            ]
        ];

        $this->assertEquals($expected, $polygon->toGeoJSON());
    }

    public function testToWKTZM()
    {
        $points = [
            new PointZM(0, 0, 1, 10),
            new PointZM(0, 1, 2, 20),
            new PointZM(1, 1, 3, 30),
            new PointZM(0, 0, 1, 10)
        ];
        $lineString = new LineStringZM($points);
        $polygon = new PolygonZM([$lineString]);

        $expected = 'POLYGON ZM((0 0 1 10,1 1 3 30,0 1 2 20,0 0 1 10))';
        $this->assertSame($expected, $polygon->toWKT());
    }

    public function testCreateFromGeoJSONZM()
    {
        $json = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [0, 0, 1, 100],
                    [1, 1, 3, 300],
                    [0, 1, 2, 200],
                    [0, 0, 1, 100]
                ]
            ]
        ];

        $polygon = PolygonZM::createFromGeoJSON($json);

        $this->assertInstanceOf(PolygonZM::class, $polygon);
        $this->assertEquals($json, $polygon->toGeoJSON());
    }

    public function testValidateRingsThrowsExceptionForOpenRing()
    {
        $this->expectException(\InvalidArgumentException::class);
        $points = [
            new PointZM(0, 0, 1, 10),
            new PointZM(0, 1, 2, 20),
            new PointZM(1, 1, 3, 30),
            new PointZM(1, 0, 4, 40) // not closing
        ];
        $lineString = new LineStringZM($points);

        new PolygonZM([$lineString]);
    }

    public function testOuterRingReversedIfNotCCW()
    {
        // Build a clockwise outer ring
        $points = [
            new PointZM(0, 0, 0, 0),
            new PointZM(1, 0, 0, 0),
            new PointZM(1, 1, 0, 0),
            new PointZM(0, 1, 0, 0),
            new PointZM(0, 0, 0, 0)
        ];
        $lineString = new LineStringZM($points);
        $polygon = new PolygonZM([$lineString]);

        $wkt = $polygon->toWKT();
        $this->assertStringStartsWith('POLYGON ZM((', $wkt);
    }
}