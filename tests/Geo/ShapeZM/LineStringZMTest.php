<?php

namespace Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use PHPUnit\Framework\TestCase;

class LineStringZMTest extends TestCase
{
    public function testCreateLineStringZM()
    {
        $points = [
            new PointZM(1, 2, 3, 1000),
            new PointZM(4, 5, 6, 1000),
            new PointZM(7, 8, 9, 1000),
        ];

        $line = new LineStringZM($points, 4326);

        $this->assertCount(3, $line->getPoints());
        $this->assertSame($points, $line->getPoints());
        $this->assertSame(4326, $line->getSRID());
    }

    public function testToWKT()
    {
        $points = [
            new PointZM(1.1, 2.2, 3.3, 4326),
            new PointZM(4.4, 5.5, 6.6, 4326),
            new PointZM(7.7, 8.8, 9.9, 4326),
        ];
        $line = new LineStringZM($points, 4326);

        $expectedWKT = 'LINESTRING ZM(1.1 2.2 3.3 4326,4.4 5.5 6.6 4326,7.7 8.8 9.9 4326)';
        $this->assertSame($expectedWKT, $line->toWKT());

        $expectedEWKT = 'SRID=4326;' . $expectedWKT;
        $this->assertSame($expectedEWKT, $line->toEWKT());

        $this->assertSame("ST_GeomFromEWKT('$expectedEWKT')", $line->ST_GeomFromEWKT());
    }

    public function testToGeoJSON()
    {
        $points = [
            new PointZM(10, 20, 30, 100),
            new PointZM(40, 50, 60, 200),
        ];
        $line = new LineStringZM($points, 3857);

        $expectedGeoJSON = [
            'type' => 'LineString',
            'coordinates' => [
                [10, 20, 30, 100],
                [40, 50, 60, 200],
            ],
        ];

        $this->assertEquals($expectedGeoJSON, $line->toGeoJSON());
    }

    public function testCreateFromGeoJSON()
    {
        $json = [
            'type' => 'LineString',
            'coordinates' => [
                [1, 2, 3, 4],
                [5, 6, 7, 8],
                [9, 10, 11, 12],
            ]
        ];

        $line = LineStringZM::createFromGeoJSON($json, 4326);

        $this->assertInstanceOf(LineStringZM::class, $line);
        $this->assertCount(3, $line->getPoints());

        $coords = array_map(fn($pt) => $pt->getCoordinates(), $line->getPoints());
        $this->assertEquals($json['coordinates'], $coords);
    }

    public function testCreateFromGeoEWKTString()
    {
        $ewkt = 'SRID=4326;LINESTRING ZM(1 2 3 4,5 6 7 8,9 10 11 12)';
        $line = LineStringZM::createFromGeoEWKTString($ewkt);

        $this->assertInstanceOf(LineStringZM::class, $line);
        $this->assertCount(3, $line->getPoints());

        $coords = array_map(fn($pt) => $pt->getCoordinates(), $line->getPoints());
        $expected = [
            [1, 2, 3, 4],
            [5, 6, 7, 8],
            [9, 10, 11, 12],
        ];
        $this->assertEquals($expected, $coords);

        $this->assertSame($ewkt, $line->toEWKT());
        $this->assertSame('LINESTRING ZM(1 2 3 4,5 6 7 8,9 10 11 12)', $line->toWKT());
    }

    public function testInvalidGeoJSONThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        LineStringZM::createFromGeoJSON([
            'type' => 'LineString',
            'coordinates' => [
                [1, 2, 3] // missing M element
            ]
        ]);
    }

    public function testEmptyPointsThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        new LineStringZM([], 4326);
    }
}