<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\PolygonZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\GeometryCollectionZ;
use PHPUnit\Framework\TestCase;

class GeometryCollectionZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A GeometryCollection with a Point and a LineString
        $point = new PointZ(1, 1, 1,4326);
        $lineString = new LineStringZ([
            new PointZ(2, 2, 1, 4326),
            new PointZ(3, 3, 1, 4326),
            new PointZ(4, 4, 1, 4326)
        ], 4326);

        $geometryCollection = new GeometryCollectionZ([$point, $lineString], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTIONZ(POINTZ(1 1 1),LINESTRING Z(2 2 1,3 3 1,4 4 1))')";
        $this->assertSame($expected, $geometryCollection->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A GeometryCollection with a Point, LineString, and Polygon
        $point = new PointZ(1, 1, 1, 4326);
        $lineString = new LineStringZ([
            new PointZ(2, 2, 1, 4326),
            new PointZ(3, 3, 1, 4326),
            new PointZ(4, 4, 1, 4326)
        ], 4326);

        // Create Polygon with outer ring and a hole
        $outerRing = new LineStringZ([
            new PointZ(0, 0, 1, 4326),
            new PointZ(0, 5, 1, 4326),
            new PointZ(5, 5, 1, 4326),
            new PointZ(5, 0, 1, 4326),
            new PointZ(0, 0, 1, 4326),
        ], 4326);

        $hole = new LineStringZ([
            new PointZ(1, 1, 1, 4326),
            new PointZ(1, 2, 1, 4326),
            new PointZ(2, 2, 1, 4326),
            new PointZ(2, 1, 1, 4326),
            new PointZ(1, 1, 1, 4326),
        ], 4326);

        $polygon = new PolygonZ([$outerRing, $hole], 4326);

        // Create the GeometryCollection with Point, LineString, and Polygon
        $geometryCollection = new GeometryCollectionZ([$point, $lineString, $polygon], 4326);

        // Construct the expected EWKT for the GeometryCollection
        $expected = "ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTIONZ(POINTZ(1 1 1),LINESTRING Z(2 2 1,3 3 1,4 4 1),POLYGON Z((0 0 1,5 0 1,5 5 1,0 5 1,0 0 1),(1 1 1,1 2 1,2 2 1,2 1 1,1 1 1)))')";
        $this->assertSame($expected, $geometryCollection->ST_GeomFromEWKT());
    }

    public function testGeoJSONGeometryCollectionZ()
    {
        $jsonData = [
            'type' => 'GeometryCollection',
            'geometries' => [

                // ------------------------ PointZ ------------------------
                [
                    'type' => 'Point',
                    'coordinates' => [10, 20, 5]
                ],

                // ------------------------ MultiPointZ ------------------------
                [
                    'type' => 'MultiPoint',
                    'coordinates' => [
                        [30, 40, 1],
                        [50, 60, 2]
                    ]
                ],

                // ------------------------ LineStringZ ------------------------
                [
                    'type' => 'LineString',
                    'coordinates' => [
                        [0, 0, 0],
                        [10, 10, 1],
                        [20, 0, 2]
                    ]
                ],

                // ------------------------ MultiLineStringZ ------------------------
                [
                    'type' => 'MultiLineString',
                    'coordinates' => [
                        [
                            [0, 0, 1],
                            [10, 0, 2],
                            [10, 10, 3]
                        ],
                        [
                            [20, 20, 4],
                            [30, 30, 5],
                            [40, 20, 6]
                        ]
                    ]
                ],

                // ------------------------ PolygonZ (CCW outer, CW hole) ------------------------
                [
                    'type' => 'Polygon',
                    'coordinates' => [
                        [   // outer ring CCW
                            [0, 0, 0],
                            [10, 0, 1],
                            [10, 10, 2],
                            [0, 10, 3],
                            [0, 0, 0]
                        ],
                        [   // inner ring CW
                            [2, 2, 0],
                            [2, 5, 1],
                            [5, 5, 2],
                            [5, 2, 3],
                            [2, 2, 0]
                        ]
                    ]
                ],

                // ------------------------ MultiPolygonZ ------------------------
                [
                    'type' => 'MultiPolygon',
                    'coordinates' => [

                        // Polygon 1
                        [
                            [   // outer ring CCW
                                [20, 20, 0],
                                [30, 20, 1],
                                [30, 30, 2],
                                [20, 30, 3],
                                [20, 20, 0]
                            ]
                        ],

                        // Polygon 2 with hole
                        [
                            [   // outer ring CCW
                                [40, 40, 0],
                                [50, 40, 1],
                                [50, 50, 2],
                                [40, 50, 3],
                                [40, 40, 0]
                            ],
                            [   // inner ring CW
                                [42, 42, 0],
                                [42, 48, 1],
                                [48, 48, 2],
                                [48, 42, 3],
                                [42, 42, 0]
                            ]
                        ]
                    ]
                ],

                // ------------------------ Nested GeometryCollectionZ ------------------------
                [
                    'type' => 'GeometryCollection',
                    'geometries' => [
                        [
                            'type' => 'Point',
                            'coordinates' => [99, 88, 7]
                        ],
                        [
                            'type' => 'LineString',
                            'coordinates' => [
                                [1, 1, 0],
                                [2, 2, 1],
                                [3, 3, 2]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $gc = GeometryCollectionZ::createFromGeoJSON($jsonData);

        $this->assertInstanceOf(GeometryCollectionZ::class, $gc);
        $this->assertEquals(4326, $gc->getSRID());

        // Round-trip
        $this->assertEquals($jsonData, $gc->toGeoJSON());
    }

}