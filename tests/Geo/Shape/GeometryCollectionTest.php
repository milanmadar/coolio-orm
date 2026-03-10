<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\Polygon;
use Milanmadar\CoolioORM\Geo\Shape2D\GeometryCollection;
use PHPUnit\Framework\TestCase;

class GeometryCollectionTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A GeometryCollection with a Point and a LineString
        $point = new Point(1, 1, 4326);
        $lineString = new LineString([
            new Point(2, 2, 4326),
            new Point(3, 3, 4326),
            new Point(4, 4, 4326)
        ], 4326);

        $geometryCollection = new GeometryCollection([$point, $lineString], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTION(POINT(1 1),LINESTRING(2 2,3 3,4 4))')";
        $this->assertSame($expected, $geometryCollection->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A GeometryCollection with a Point, LineString, and Polygon
        $point = new Point(1, 1, 4326);
        $lineString = new LineString([
            new Point(2, 2, 4326),
            new Point(3, 3, 4326),
            new Point(4, 4, 4326)
        ], 4326);

        // Create Polygon with outer ring and a hole
        $outerRing = new LineString([
            new Point(0, 0, 4326),
            new Point(0, 5, 4326),
            new Point(5, 5, 4326),
            new Point(5, 0, 4326),
            new Point(0, 0, 4326),
        ], 4326);

        $hole = new LineString([
            new Point(1, 1, 4326),
            new Point(1, 2, 4326),
            new Point(2, 2, 4326),
            new Point(2, 1, 4326),
            new Point(1, 1, 4326),
        ], 4326);

        $polygon = new Polygon([$outerRing, $hole], 4326);

        // Create the GeometryCollection with Point, LineString, and Polygon
        $geometryCollection = new GeometryCollection([$point, $lineString, $polygon], 4326);

        // Construct the expected EWKT for the GeometryCollection
        $expected = "ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTION(POINT(1 1),LINESTRING(2 2,3 3,4 4),POLYGON((0 0,5 0,5 5,0 5,0 0),(1 1,1 2,2 2,2 1,1 1)))')";
        $this->assertSame($expected, $geometryCollection->ST_GeomFromEWKT());
    }

    public function testGeometryCollectionGeoJSON()
    {
        $srid = 4326;

        $jsonData = [
            'type' => 'GeometryCollection',
            'geometries' => [
                // Point
                [
                    'type' => 'Point',
                    'coordinates' => [10, 20]
                ],

                // MultiPoint
                [
                    'type' => 'MultiPoint',
                    'coordinates' => [
                        [30, 40],
                        [50, 60]
                    ]
                ],

                // LineString
                [
                    'type' => 'LineString',
                    'coordinates' => [
                        [0, 0],
                        [10, 10],
                        [20, 0]
                    ]
                ],

                // MultiLineString
                [
                    'type' => 'MultiLineString',
                    'coordinates' => [
                        [
                            [0, 0],
                            [10, 0],
                            [10, 10]
                        ],
                        [
                            [20, 20],
                            [30, 30],
                            [40, 20]
                        ]
                    ]
                ],

                // Polygon (outer ring CCW, one hole CW)
                [
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [0, 0],
                            [10, 0],
                            [10, 10],
                            [0, 10],
                            [0, 0]
                        ],
                        [
                            [2, 2],
                            [5, 5],
                            [5, 2],
                            [2, 2]
                        ]
                    ]
                ],

                // MultiPolygon (outer rings CCW, inner rings CW)
                [
                    'type' => 'MultiPolygon',
                    'coordinates' => [
                        [
                            [
                                [0, 0],
                                [5, 0],
                                [5, 5],
                                [0, 5],
                                [0, 0]
                            ]
                        ],
                        [
                            [
                                [10, 10],
                                [15, 10],
                                [15, 15],
                                [10, 15],
                                [10, 10]
                            ],
                            [
                                [11, 11],
                                [14, 14],
                                [14, 11],
                                [11, 11]
                            ]
                        ]
                    ]
                ],

                // Nested GeometryCollection
                [
                    'type' => 'GeometryCollection',
                    'geometries' => [
                        [
                            'type' => 'Point',
                            'coordinates' => [99, 88]
                        ],
                        [
                            'type' => 'LineString',
                            'coordinates' => [
                                [1, 1],
                                [2, 2],
                                [3, 3]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $geometryCollection = GeometryCollection::createFromGeoJSON($jsonData);

        $this->assertEquals($srid, $geometryCollection->getSRID());

        $this->assertEquals($jsonData, $geometryCollection->toGeoJSON());
    }

}