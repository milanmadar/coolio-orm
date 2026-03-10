<?php

namespace Geo;

use Milanmadar\CoolioORM\Geo\Feature;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiPolygon;
use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\Polygon;
use PHPUnit\Framework\TestCase;

class FeatureTest extends TestCase
{
    public function testCreateFeatureWithPoint()
    {
        $geometry = new Point(10.5, 20.25, 3857);

        $feature = new Feature($geometry, ['name' => 'Test'], 'abc123');

        $this->assertSame($geometry, $feature->getGeometry());
        $this->assertEquals(['name' => 'Test'], $feature->getProperties());
        $this->assertSame('abc123', $feature->getId());
    }

    public function testFeatureWithPoint()
    {
        $point = new Point(12.34, 56.78, 4326);
        $feature = new Feature($point, ['name' => 'Test'], 'feature1');

        // WKT
        $this->assertSame('POINT(12.34 56.78)', $feature->toWKT());

        // EWKT
        $this->assertSame('SRID=4326;POINT(12.34 56.78)', $feature->toEWKT());

        // ST_GeomFromEWKT
        $this->assertSame("ST_GeomFromEWKT('SRID=4326;POINT(12.34 56.78)')", $feature->ST_GeomFromEWKT());
    }

    public function testFeatureWithLineString()
    {
        $points = [
            new Point(1, 2),
            new Point(3, 4),
            new Point(5, 6)
        ];
        $line = new LineString($points);
        $feature = new Feature($line);

        // WKT
        $this->assertSame('LINESTRING(1 2,3 4,5 6)', $feature->toWKT());

        // EWKT
        $expectedEWKT = 'SRID=' . $_ENV['GEO_DEFAULT_SRID'] . ';LINESTRING(1 2,3 4,5 6)';
        $this->assertSame($expectedEWKT, $feature->toEWKT());

        // ST_GeomFromEWKT
        $this->assertSame("ST_GeomFromEWKT('$expectedEWKT')", $feature->__toString());
    }

    public function testFeatureWithPropertiesAndId()
    {
        $point = new Point(0, 0);
        $feature = new Feature($point, ['foo' => 'bar'], 123);

        $geoJson = $feature->toGeoJSON();

        $this->assertEquals([
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [0, 0],
            ],
            'properties' => ['foo' => 'bar'],
            'id' => 123
        ], $geoJson);

        // WKT still matches geometry
        $this->assertSame('POINT(0 0)', $feature->toWKT());
    }

    public function testFeatureWithPolygon()
    {
        $polygon = new Polygon([
            new LineString([
                new Point(0, 0),
                new Point(0, 10),
                new Point(10, 10),
                new Point(10, 0),
                new Point(0, 0)
            ])
        ]);
        $feature = new Feature($polygon);

        $expected = 'POLYGON((0 0,10 0,10 10,0 10,0 0))';
        $this->assertSame($expected, $feature->toWKT());

        $expectedEWKT = 'SRID=' . $_ENV['GEO_DEFAULT_SRID'] . ';'.$expected;
        $this->assertSame($expectedEWKT, $feature->toEWKT());
        $this->assertSame("ST_GeomFromEWKT('$expectedEWKT')", $feature->__toString());
    }

    public function testFeatureWithMultiPolygon()
    {
        $multiPolygon = new MultiPolygon([
            new Polygon([
                new LineString([
                    new Point(0, 0),
                    new Point(0, 5),
                    new Point(5, 5),
                    new Point(5, 0),
                    new Point(0, 0)
                ])
            ]),
            new Polygon([
                new LineString([
                    new Point(10, 10),
                    new Point(10, 15),
                    new Point(15, 15),
                    new Point(15, 10),
                    new Point(10, 10)
                ])
            ])
        ]);
        $feature = new Feature($multiPolygon);

        $expected = 'MULTIPOLYGON(((0 0,5 0,5 5,0 5,0 0)),((10 10,15 10,15 15,10 15,10 10)))';
        $this->assertSame($expected, $feature->toWKT());

        $expectedEWKT = 'SRID=' . $_ENV['GEO_DEFAULT_SRID'] . ';'.$expected;
        $this->assertSame($expectedEWKT, $feature->toEWKT());
        $this->assertSame("ST_GeomFromEWKT('$expectedEWKT')", $feature->__toString());
    }

    public function testCreateFromGeoJSON()
    {
        $json = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [30, 10]
            ],
            'properties' => [
                'foo' => 'bar'
            ],
            'id' => 99
        ];

        $feature = Feature::createFromGeoJSON($json);

        // Geometry must be a Point instance
        $this->assertInstanceOf(Point::class, $feature->getGeometry());

        // SRID comes from default environment
        $this->assertEquals($_ENV['GEO_DEFAULT_SRID'], $feature->getGeometry()->getSRID());

        // Properties preserved
        $this->assertEquals(['foo' => 'bar'], $feature->getProperties());

        // Id preserved
        $this->assertSame(99, $feature->getId());

        // Round-trip must match exactly
        $this->assertEquals($json, $feature->toGeoJSON());
    }

    public function testCreateFromGeoJSONWithNullProperties()
    {
        $json = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [5, 6]
            ],
            'properties' => null
        ];

        $feature = Feature::createFromGeoJSON($json);

        $this->assertInstanceOf(Point::class, $feature->getGeometry());
        $this->assertNull($feature->getProperties());
        $this->assertEquals($json, $feature->toGeoJSON());
    }

    public function testToGeoJSON()
    {
        $geometry = new Point(1, 2, 4326);
        $feature = new Feature($geometry, ['a' => 1], 'id-1');

        $expected = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [1, 2]
            ],
            'properties' => ['a' => 1],
            'id' => 'id-1'
        ];

        $this->assertEquals($expected, $feature->toGeoJSON());
    }
}