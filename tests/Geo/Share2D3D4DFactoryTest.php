<?php

namespace Geo;

use Milanmadar\CoolioORM\Geo\Shape2D3D4DFactory;
use PHPUnit\Framework\TestCase;

class Share2D3D4DFactoryTest extends TestCase
{
    public function testCreateFromGeoJSONString_Point2D()
    {
        $json = '{"type":"Point","coordinates":[1,2]}';

        $geom = Shape2D3D4DFactory::createFromGeoJSONString($json, 4326);

        $this->assertInstanceOf(\Milanmadar\CoolioORM\Geo\Shape2D\Point::class, $geom);
    }

    public function testCreateFromGeoJSONString_PointZ()
    {
        $json = '{"type":"Point","coordinates":[1,2,3]}';

        $geom = Shape2D3D4DFactory::createFromGeoJSONString($json, 4326);

        $this->assertInstanceOf(\Milanmadar\CoolioORM\Geo\ShapeZ\PointZ::class, $geom);
    }

    public function testCreateFromGeoJSONString_PointZM()
    {
        $json = '{"type":"Point","coordinates":[1,2,3,100]}';

        $geom = Shape2D3D4DFactory::createFromGeoJSONString($json, 4326);

        $this->assertInstanceOf(\Milanmadar\CoolioORM\Geo\ShapeZM\PointZM::class, $geom);
    }

    public function testCreateFromGeoJSONString_LineStringZM()
    {
        $json = '{"type":"LineString","coordinates":[[0,0,1,100],[1,1,2,100]]}';

        $geom = Shape2D3D4DFactory::createFromGeoJSONString($json, 4326);

        $this->assertInstanceOf(\Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM::class, $geom);
    }

    public function testCreateFromGeoJSONString_PolygonZM()
    {
        $json = '{"type":"Polygon","coordinates":[[[0,0,1,100],[0,5,2,100],[5,5,3,100],[5,0,4,100],[0,0,1,100]]]}';

        $geom = Shape2D3D4DFactory::createFromGeoJSONString($json, 4326);

        $this->assertInstanceOf(\Milanmadar\CoolioORM\Geo\ShapeZM\PolygonZM::class, $geom);
    }

    public function testCreateFromGeoJSONString_InvalidJSON()
    {
        $this->expectException(\InvalidArgumentException::class);

        Shape2D3D4DFactory::createFromGeoJSONString('{invalid}', 4326);
    }

    public function testCreateFromGeoEWKTString_PointZM()
    {
        $ewkt = 'SRID=4326;POINT ZM(1 2 3 100)';

        $geom = Shape2D3D4DFactory::createFromGeoEWKTString($ewkt);

        $this->assertInstanceOf(\Milanmadar\CoolioORM\Geo\ShapeZM\PointZM::class, $geom);
    }

    public function testCreateFromGeoEWKTString_LineStringZ()
    {
        $ewkt = 'SRID=4326;LINESTRING Z(0 0 1,1 1 2)';

        $geom = Shape2D3D4DFactory::createFromGeoEWKTString($ewkt);

        $this->assertInstanceOf(\Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ::class, $geom);
    }

    public function testCreateFromGeoEWKTString_PolygonZM()
    {
        $ewkt = 'SRID=4326;POLYGON ZM((0 0 1 100,0 5 2 100,5 5 3 100,5 0 4 100,0 0 1 100))';

        $geom = Shape2D3D4DFactory::createFromGeoEWKTString($ewkt);

        $this->assertInstanceOf(\Milanmadar\CoolioORM\Geo\ShapeZM\PolygonZM::class, $geom);
    }

    public function testCreateFromGeoEWKTString_CircularStringZM()
    {
        $ewkt = 'SRID=4326;CIRCULARSTRING ZM(0 0 1 100,1 1 2 100,2 0 3 100)';

        $geom = Shape2D3D4DFactory::createFromGeoEWKTString($ewkt);

        $this->assertInstanceOf(\Milanmadar\CoolioORM\Geo\ShapeZM\CircularStringZM::class, $geom);
    }

    public function testCreateFromGeoEWKTString_Invalid()
    {
        $this->expectException(\InvalidArgumentException::class);

        Shape2D3D4DFactory::createFromGeoEWKTString('POINT(1 2)');
    }

    public function testCreateFromGeoJSONString_Feature()
    {
        $json = '{
        "type":"Feature",
        "geometry":{"type":"Point","coordinates":[1,2]},
        "properties":{"name":"test"}
    }';

        $geom = Shape2D3D4DFactory::createFromGeoJSONString($json, 4326);

        $this->assertInstanceOf(\Milanmadar\CoolioORM\Geo\Feature::class, $geom);
    }

    public function testCreateFromGeoJSONString_FeatureCollection()
    {
        $json = '{
        "type": "FeatureCollection",
        "features": [
            {
                "type": "Feature",
                "geometry": {
                    "type": "Point",
                    "coordinates": [1, 2, 3, 100]
                },
                "properties": {"id": 1}
            },
            {
                "type": "Feature",
                "geometry": {
                    "type": "LineString",
                    "coordinates": [
                        [0, 0, 1, 100],
                        [1, 1, 2, 100]
                    ]
                },
                "properties": {"id": 2}
            }
        ]
    }';

        $collection = Shape2D3D4DFactory::createFromGeoJSONString($json, 4326);

        // Assert correct factory output type
        $this->assertInstanceOf(
            \Milanmadar\CoolioORM\Geo\FeatureCollection::class,
            $collection
        );

        // Must contain 2 features
        $this->assertCount(2, $collection->getFeatures());

        // Feature 1 → PointZM
        $this->assertInstanceOf(
            \Milanmadar\CoolioORM\Geo\Feature::class,
            $collection->getFeatures()[0]
        );
        $this->assertInstanceOf(
            \Milanmadar\CoolioORM\Geo\ShapeZM\PointZM::class,
            $collection->getFeatures()[0]->getGeometry()
        );

        // Feature 2 → LineStringZM
        $this->assertInstanceOf(
            \Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM::class,
            $collection->getFeatures()[1]->getGeometry()
        );

        // Verify SRID
        $this->assertEquals(
            4326,
            $collection->getFeatures()[0]->getGeometry()->getSrid()
        );
    }
}