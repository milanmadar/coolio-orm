<?php

namespace Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\ShapeZM\PolygonZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\GeometryCollectionZM;

use PHPUnit\Framework\TestCase;

class GeometryCollectionZMTest extends TestCase
{
    public function testCreateFromConstructor()
    {
        $p1 = new PointZM(0, 0, 0, 0);
        $p2 = new PointZM(0, 3, 0, 0);
        $p3 = new PointZM(3, 3, 0, 0);
        $p4 = new PointZM(3, 0, 0, 0);
        $p5 = new PointZM(0, 0, 0, 0); // close the ring

        $line = new LineStringZM([$p1, $p2, $p3, $p4, $p5]);
        $poly = new PolygonZM([$line]);

        $gc = new GeometryCollectionZM([$p1, $line, $poly]);

        $this->assertCount(3, $gc->getGeometries());
        $this->assertSame($p1, $gc->getGeometries()[0]);
        $this->assertSame($line, $gc->getGeometries()[1]);
        $this->assertSame($poly, $gc->getGeometries()[2]);
    }

    public function testToWKT()
    {
        $p = new PointZM(1, 2, 3, 4);
        $line = new LineStringZM([new PointZM(5, 6, 7, 8), new PointZM(9, 10, 11, 12)]);
        $poly = new PolygonZM([new LineStringZM([new PointZM(13,14,15,16), new PointZM(17,18,19,20), new PointZM(13,18,19,20), new PointZM(13,14,15,16)])]);

        $gc = new GeometryCollectionZM([$p, $line, $poly]);

        $expected = sprintf(
            'GEOMETRYCOLLECTION ZM(%s,%s,%s)',
            $p->toWKT(),
            $line->toWKT(),
            $poly->toWKT()
        );

        $this->assertSame($expected, $gc->toWKT());
    }

    public function testToGeoJSON()
    {
        $p = new PointZM(1, 2, 3, 4);
        $line = new LineStringZM([new PointZM(5, 6, 7, 8), new PointZM(9, 10, 11, 12)]);

        $gc = new GeometryCollectionZM([$p, $line]);

        $expected = [
            'type' => 'GeometryCollection',
            'geometries' => [
                $p->toGeoJSON(),
                $line->toGeoJSON()
            ]
        ];

        $this->assertEquals($expected, $gc->toGeoJSON());
    }

    public function testCreateFromGeoJSON()
    {
        $json = [
            'type' => 'GeometryCollection',
            'geometries' => [
                ['type' => 'Point', 'coordinates' => [1, 2, 3, 4]],
                [
                    'type' => 'LineString',
                    'coordinates' => [
                        [5, 6, 7, 8],
                        [9, 10, 11, 12]
                    ]
                ]
            ]
        ];

        $gc = GeometryCollectionZM::createFromGeoJSON($json, 4326);

        $this->assertCount(2, $gc->getGeometries());
        $this->assertEquals($json, $gc->toGeoJSON());
    }

    public function testCreateFromGeoEWKTString()
    {
        $ewkt = 'SRID=4326;GEOMETRYCOLLECTION ZM(POINT ZM(1 2 3 4),LINESTRING ZM(5 6 7 8,9 10 11 12))';

        $gc = GeometryCollectionZM::createFromGeoEWKTString($ewkt);

        $this->assertCount(2, $gc->getGeometries());

        $expectedGeoJSON = [
            'type' => 'GeometryCollection',
            'geometries' => [
                ['type' => 'Point', 'coordinates' => [1, 2, 3, 4]],
                [
                    'type' => 'LineString',
                    'coordinates' => [
                        [5, 6, 7, 8],
                        [9, 10, 11, 12]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedGeoJSON, $gc->toGeoJSON());
    }

    public function testEmptyGeometriesThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new GeometryCollectionZM([]);
    }

    public function testNestedGeometryCollectionZM()
    {
        $p1 = new PointZM(1, 2, 3, 4);
        $p2 = new PointZM(5, 6, 7, 8);
        $innerGC = new GeometryCollectionZM([$p1, $p2]);

        $gc = new GeometryCollectionZM([$innerGC]);

        $this->assertCount(1, $gc->getGeometries());
        $this->assertSame($innerGC, $gc->getGeometries()[0]);
        $this->assertEquals(
            [
                'type' => 'GeometryCollection',
                'geometries' => [
                    [
                        'type' => 'GeometryCollection',
                        'geometries' => [
                            $p1->toGeoJSON(),
                            $p2->toGeoJSON()
                        ]
                    ]
                ]
            ],
            $gc->toGeoJSON()
        );
    }

    public function testCreateFromConstructor2()
    {
        // --- PointZM ---
        $point = new PointZM(1, 2, 3, 4);

        // --- LineStringZM ---
        $p1 = new PointZM(5, 6, 7, 8);
        $p2 = new PointZM(9, 10, 11, 12);
        $line = new LineStringZM([$p1, $p2]);

        // --- PolygonZM ---
        // Outer ring CCW
        $r1 = [
            new PointZM(0, 0, 0, 100),
            new PointZM(0, 3, 0, 100),
            new PointZM(3, 3, 0, 100),
            new PointZM(3, 0, 0, 100),
            new PointZM(0, 0, 0, 100), // close
        ];
        $polygon = new PolygonZM([new LineStringZM($r1)]);

        // --- GeometryCollectionZM ---
        $gc = new GeometryCollectionZM([$point, $line, $polygon]);

        $geometries = $gc->getGeometries();
        $this->assertCount(3, $geometries);

        // Type checks
        $this->assertInstanceOf(PointZM::class, $geometries[0]);
        $this->assertInstanceOf(LineStringZM::class, $geometries[1]);
        $this->assertInstanceOf(PolygonZM::class, $geometries[2]);
    }

    public function testToWKT2()
    {
        $point = new PointZM(1, 2, 3, 4);
        $line = new LineStringZM([new PointZM(5,6,7,8), new PointZM(9,10,11,12)]);
        $polygon = new PolygonZM([new LineStringZM([
            new PointZM(0,0,0,100),
            new PointZM(3,0,0,100),
            new PointZM(3,3,0,100),
            new PointZM(0,3,0,100),
            new PointZM(0,0,0,100),
        ])]);

        $gc = new GeometryCollectionZM([$point, $line, $polygon]);

        $expectedWKT = 'GEOMETRYCOLLECTION ZM('
            . 'POINT ZM(1 2 3 4),'
            . 'LINESTRING ZM(5 6 7 8,9 10 11 12),'
            . 'POLYGON ZM((0 0 0 100,3 0 0 100,3 3 0 100,0 3 0 100,0 0 0 100))'
            . ')';

        $this->assertSame($expectedWKT, $gc->toWKT());
    }

    public function testToGeoJSON2()
    {
        $point = new PointZM(1, 2, 3, 4);
        $line = new LineStringZM([new PointZM(5,6,7,8), new PointZM(9,10,11,12)]);
        $polygon = new PolygonZM([new LineStringZM([
            new PointZM(0,0,0,100),
            new PointZM(3,0,0,100),
            new PointZM(3,3,0,100),
            new PointZM(0,3,0,100),
            new PointZM(0,0,0,100),
        ])]);

        $gc = new GeometryCollectionZM([$point, $line, $polygon]);

        $expectedGeoJSON = [
            'type' => 'GeometryCollection',
            'geometries' => [
                [
                    'type' => 'Point',
                    'coordinates' => [1, 2, 3, 4],
                ],
                [
                    'type' => 'LineString',
                    'coordinates' => [
                        [5,6,7,8],
                        [9,10,11,12]
                    ]
                ],
                [
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [0,0,0,100],
                            [3,0,0,100],
                            [3,3,0,100],
                            [0,3,0,100],
                            [0,0,0,100]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedGeoJSON, $gc->toGeoJSON());
    }
}