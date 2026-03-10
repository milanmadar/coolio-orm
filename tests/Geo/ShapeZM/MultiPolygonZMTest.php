<?php

namespace Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\ShapeZM\MultiPolygonZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PolygonZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use PHPUnit\Framework\TestCase;

class MultiPolygonZMTest extends TestCase
{
    public function testCreateMultiPolygonZM()
    {
        // Outer ring CCW
        $ring1 = new LineStringZM([
            new PointZM(0, 0, 10, 100),
            new PointZM(0, 5, 10, 100),
            new PointZM(5, 5, 10, 100),
            new PointZM(5, 0, 10, 100),
            new PointZM(0, 0, 10, 100),
        ]);

        // Inner ring CW (hole)
        $ring2 = new LineStringZM([
            new PointZM(1, 1, 10, 100),
            new PointZM(2, 1, 10, 100),
            new PointZM(2, 2, 10, 100),
            new PointZM(1, 2, 10, 100),
            new PointZM(1, 1, 10, 100),
        ]);

        $polygon1 = new PolygonZM([$ring1, $ring2]);

        // Second polygon (single outer ring)
        $ring3 = new LineStringZM([
            new PointZM(10, 10, 20, 200),
            new PointZM(10, 15, 20, 200),
            new PointZM(15, 15, 20, 200),
            new PointZM(15, 10, 20, 200),
            new PointZM(10, 10, 20, 200),
        ]);

        $polygon2 = new PolygonZM([$ring3]);

        $multi = new MultiPolygonZM([$polygon1, $polygon2]);

        // === WKT ===
        $expectedWKT = 'MULTIPOLYGON ZM('
            . '((0 0 10 100,5 0 10 100,5 5 10 100,0 5 10 100,0 0 10 100),'
            . '(1 1 10 100,1 2 10 100,2 2 10 100,2 1 10 100,1 1 10 100)),'
            . '((10 10 20 200,15 10 20 200,15 15 20 200,10 15 20 200,10 10 20 200)))';
        $this->assertSame($expectedWKT, $multi->toWKT());

        // === GeoJSON ===
        $geoJson = $multi->toGeoJSON();

        // Type check
        $this->assertSame('MultiPolygon', $geoJson['type']);

        // Two polygons
        $this->assertCount(2, $geoJson['coordinates']);

        // Polygon 1: 2 rings
        $this->assertCount(2, $geoJson['coordinates'][0]);
        $this->assertCount(5, $geoJson['coordinates'][0][0]); // outer
        $this->assertCount(5, $geoJson['coordinates'][0][1]); // hole

        // Polygon 2: 1 ring
        $this->assertCount(1, $geoJson['coordinates'][1]);
        $this->assertCount(5, $geoJson['coordinates'][1][0]);

        // Spot check coordinates (ignore order for rings, just ensure values exist)
        $allCoords = array_merge(
            ...array_map(fn($poly) => array_merge(...$poly), $geoJson['coordinates'])
        );

        // X, Y, Z, M values
        $expectedValues = [
            [0,0,10,100],[0,5,10,100],[5,5,10,100],[5,0,10,100],
            [1,1,10,100],[2,1,10,100],[2,2,10,100],[1,2,10,100],
            [10,10,20,200],[10,15,20,200],[15,15,20,200],[15,10,20,200]
        ];

        foreach ($expectedValues as $val) {
            $found = false;
            foreach ($allCoords as $coord) {
                if ($coord[0] == $val[0] && $coord[1] == $val[1] && $coord[2] == $val[2] && $coord[3] == $val[3]) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Coordinate ' . implode(',', $val) . ' not found in GeoJSON');
        }
    }

    public function testMultiPolygonZMWKTExample()
    {
        // Outer ring of first polygon (ZM)
        $ring1 = new LineStringZM([
            new PointZM(0, 0, 0, 101),
            new PointZM(0, 3, 0, 100),
            new PointZM(3, 3, 0, 100),
            new PointZM(3, 0, 0, 100),
            new PointZM(0, 0, 0, 101),
        ]);

        $polygon1 = new PolygonZM([$ring1]);

        // Outer ring of second polygon (ZM)
        $ring2 = new LineStringZM([
            new PointZM(4, 4, 4, 102),
            new PointZM(4, 6, 4, 100),
            new PointZM(6, 6, 4, 100),
            new PointZM(6, 4, 4, 100),
            new PointZM(4, 4, 4, 102),
        ]);

        $polygon2 = new PolygonZM([$ring2]);

        $multi = new MultiPolygonZM([$polygon1, $polygon2]);

        // Expected WKT
        $expectedWKT = 'MULTIPOLYGON ZM(((0 0 0 101,3 0 0 100,3 3 0 100,0 3 0 100,0 0 0 101)),((4 4 4 102,6 4 4 100,6 6 4 100,4 6 4 100,4 4 4 102)))';

        $this->assertSame($expectedWKT, $multi->toWKT());
    }

    public function testCreateFromEWKTString()
    {
        $ewkt = 'SRID=4326;MULTIPOLYGON ZM(((0 0 0 101,0 3 0 100,3 3 0 100,3 0 0 100,0 0 0 101)),((4 4 4 102,4 6 4 100,6 6 4 100,6 4 4 100,4 4 4 102)))';

        $multiPolygon = MultiPolygonZM::createFromGeoEWKTString($ewkt);

        $expectedWKT = 'MULTIPOLYGON ZM(((0 0 0 101,3 0 0 100,3 3 0 100,0 3 0 100,0 0 0 101)),((4 4 4 102,6 4 4 100,6 6 4 100,4 6 4 100,4 4 4 102)))';

        $this->assertSame($expectedWKT, $multiPolygon->toWKT());

        $expectedGeoJSON = [
            'type' => 'MultiPolygon',
            'coordinates' => [
                [
                    [
                        [0, 0, 0, 101],
                        [3, 0, 0, 100],
                        [3, 3, 0, 100],
                        [0, 3, 0, 100],
                        [0, 0, 0, 101],
                    ]
                ],
                [
                    [
                        [4, 4, 4, 102],
                        [6, 4, 4, 100],
                        [6, 6, 4, 100],
                        [4, 6, 4, 100],
                        [4, 4, 4, 102],
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedGeoJSON, $multiPolygon->toGeoJSON());
        $this->assertEquals(4326, $multiPolygon->getSRID());
    }

    public function testEmptyMultiPolygonZMThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MultiPolygonZM([]);
    }
}