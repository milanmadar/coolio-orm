<?php

namespace Geo;

use Milanmadar\CoolioORM\Geo\Feature;
use Milanmadar\CoolioORM\Geo\FeatureCollection;
use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use PHPUnit\Framework\TestCase;

class FeatureCollectionTest extends TestCase
{
    public function testCreate()
    {
        $f1 = new Feature(new Point(1, 2));
        $f2 = new Feature(new Point(3, 4));

        $collection = new FeatureCollection([$f1, $f2]);

        $this->assertEquals($_ENV['GEO_DEFAULT_SRID'], $collection->getSRID());
        $this->assertCount(2, $collection->getFeatures());
    }

    public function testGeoJSON()
    {
        $jsonData = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [30, 10]
                    ],
                    'properties' => [
                        'name' => 'A'
                    ]
                ],
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [10, 10],
                            [20, 20],
                            [30, 30]
                        ]
                    ],
                    'properties' => [
                        'name' => 'B'
                    ]
                ]
            ]
        ];

        $collection = FeatureCollection::createFromGeoJSON($jsonData);

        $this->assertEquals(4326, $collection->getSRID());
        $this->assertCount(2, $collection->getFeatures());

        $this->assertEquals($jsonData, $collection->toGeoJSON());
    }

    public function testToWKTThrows()
    {
        $this->expectException(\RuntimeException::class);

        $collection = new FeatureCollection([
            new Feature(new Point(1, 2))
        ]);

        $collection->toWKT();
    }

    public function testToEWKTThrows()
    {
        $this->expectException(\RuntimeException::class);

        $collection = new FeatureCollection([
            new Feature(new Point(1, 2))
        ]);

        $collection->toEWKT();
    }
}