<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape2D\Feature;
use Milanmadar\CoolioORM\Geo\Shape2D\Point;
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