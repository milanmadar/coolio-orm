<?php

namespace Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\ShapeZM\MultiLineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use PHPUnit\Framework\TestCase;

class MultiLineStringZMTest extends TestCase
{
    public function testCreateFromConstructor()
    {
        $points1 = [
            new PointZM(1, 2, 3, 4),
            new PointZM(5, 6, 7, 8),
        ];
        $points2 = [
            new PointZM(9, 10, 11, 12),
            new PointZM(13, 14, 15, 16),
        ];

        $lines = [
            new LineStringZM($points1),
            new LineStringZM($points2),
        ];

        $mls = new MultiLineStringZM($lines);

        $this->assertSame($lines, $mls->getLineStrings());
    }

    public function testToWKT()
    {
        $points1 = [
            new PointZM(1, 2, 3, 4),
            new PointZM(5, 6, 7, 8),
        ];
        $points2 = [
            new PointZM(9, 10, 11, 12),
            new PointZM(13, 14, 15, 16),
        ];

        $lines = [
            new LineStringZM($points1),
            new LineStringZM($points2),
        ];

        $mls = new MultiLineStringZM($lines);

        $expected = 'MULTILINESTRING ZM((1 2 3 4,5 6 7 8),(9 10 11 12,13 14 15 16))';
        $this->assertSame($expected, $mls->toWKT());
    }

    public function testToGeoJSON()
    {
        $points1 = [
            new PointZM(1, 2, 3, 4),
            new PointZM(5, 6, 7, 8),
        ];
        $points2 = [
            new PointZM(9, 10, 11, 12),
            new PointZM(13, 14, 15, 16),
        ];

        $lines = [
            new LineStringZM($points1),
            new LineStringZM($points2),
        ];

        $mls = new MultiLineStringZM($lines);

        $expected = [
            'type' => 'MultiLineString',
            'coordinates' => [
                [[1, 2, 3, 4], [5, 6, 7, 8]],
                [[9, 10, 11, 12], [13, 14, 15, 16]],
            ]
        ];

        $this->assertEquals($expected, $mls->toGeoJSON());
    }

    public function testCreateFromGeoJSON()
    {
        $json = [
            'type' => 'MultiLineString',
            'coordinates' => [
                [[1, 2, 3, 4], [5, 6, 7, 8]],
                [[9, 10, 11, 12], [13, 14, 15, 16]],
            ]
        ];

        $mls = MultiLineStringZM::createFromGeoJSON($json);

        $this->assertCount(2, $mls->getLineStrings());
        $this->assertEquals($json, $mls->toGeoJSON());
    }

    public function testCreateFromEWKT()
    {
        $ewkt = 'SRID=4326;MULTILINESTRING ZM((1 2 3 4,5 6 7 8),(9 10 11 12,13 14 15 16))';
        $mls = MultiLineStringZM::createFromGeoEWKTString($ewkt);

        $this->assertCount(2, $mls->getLineStrings());

        $expectedGeoJSON = [
            'type' => 'MultiLineString',
            'coordinates' => [
                [[1, 2, 3, 4], [5, 6, 7, 8]],
                [[9, 10, 11, 12], [13, 14, 15, 16]],
            ]
        ];

        $this->assertEquals($expectedGeoJSON, $mls->toGeoJSON());
    }

    public function testValidationFailsOnEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MultiLineStringZM([]);
    }

    public function testValidationFailsOnSinglePointLineString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $line = new LineStringZM([new PointZM(1, 2, 3, 4)]);
        new MultiLineStringZM([$line]);
    }
}