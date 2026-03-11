<?php

namespace Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\Shape2D3D4DFactory;
use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use PHPUnit\Framework\TestCase;

class PointZMTest extends TestCase
{
    public function testCreatePointZM()
    {
        $point = new PointZM(10.1, 20.2, 5.3, 1234567890.0, 4326);

        $this->assertSame(10.1, $point->getX());
        $this->assertSame(20.2, $point->getY());
        $this->assertSame(5.3, $point->getZ());
        $this->assertSame(1234567890.0, $point->getM());
        $this->assertSame(4326, $point->getSRID());
    }

    public function testToWKTAndEWKT()
    {
        $point = new PointZM(1, 2, 3, 4, 3857);

        $expectedWKT = 'POINT ZM(1 2 3 4)';
        $expectedEWKT = 'SRID=3857;POINT ZM(1 2 3 4)';
        $expectedST = "ST_GeomFromEWKT('SRID=3857;POINT ZM(1 2 3 4)')";

        $this->assertSame($expectedWKT, $point->toWKT());
        $this->assertSame($expectedEWKT, $point->toEWKT());
        $this->assertSame($expectedST, $point->ST_GeomFromEWKT());
    }

    public function testToGeoJSON()
    {
        $point = new PointZM(10, 20, 30, 1772995662.97);

        $geojson = $point->toGeoJSON();

        $this->assertEquals([
            'type' => 'Point',
            'coordinates' => [10, 20, 30, 1772995662.97],
        ], $geojson);
    }

    public function testCreateFromGeoJSON()
    {
        $json = [
            'type' => 'Point',
            'coordinates' => [1.1, 2.2, 3.3, 9999.99]
        ];

        $point = PointZM::createFromGeoJSON($json, 4326);

        $this->assertSame(1.1, $point->getX());
        $this->assertSame(2.2, $point->getY());
        $this->assertSame(3.3, $point->getZ());
        $this->assertSame(9999.99, $point->getM());
        $this->assertSame(4326, $point->getSRID());
        $this->assertEquals($json, $point->toGeoJSON());
    }

    public function testCreateFromGeoEWKTString()
    {
        $ewkt = 'SRID=4326;POINT ZM(10 20 30 1772995662.97)';

        $point = PointZM::createFromGeoEWKTString($ewkt);

        $this->assertEquals(10, $point->getX());
        $this->assertEquals(20, $point->getY());
        $this->assertEquals(30, $point->getZ());
        $this->assertEquals(1772995662.97, $point->getM());
        $this->assertEquals(4326, $point->getSRID());
    }

    public function testFactory()
    {
        $json = [
            'type' => 'Point',
            'coordinates' => [1.1, 2.2, 3.3, 9999.99]
        ];

        $point = Shape2D3D4DFactory::createFromGeoJSON($json, 4326);

        $this->assertInstanceOf(PointZM::class, $point);

        $this->assertSame(1.1, $point->getX());
        $this->assertSame(2.2, $point->getY());
        $this->assertSame(3.3, $point->getZ());
        $this->assertSame(9999.99, $point->getM());
        $this->assertSame(4326, $point->getSRID());
        $this->assertEquals($json, $point->toGeoJSON());
    }
}