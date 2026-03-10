<?php

namespace Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\ShapeZM\MultiPointZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use PHPUnit\Framework\TestCase;

class MultiPointZMTest extends TestCase
{
    public function testCreateMultiPointZM()
    {
        $points = [
            new PointZM(1, 2, 3, 4),
            new PointZM(5, 6, 7, 8)
        ];

        $mp = new MultiPointZM($points);

        $this->assertSame($points, $mp->getPoints());
    }

    public function testToWKT()
    {
        $points = [
            new PointZM(1, 2, 3, 4),
            new PointZM(5, 6, 7, 8)
        ];

        $mp = new MultiPointZM($points);

        $expected = 'MULTIPOINT ZM((1 2 3 4),(5 6 7 8))';
        $this->assertSame($expected, $mp->toWKT());
    }

    public function testToGeoJSON()
    {
        $points = [
            new PointZM(10, 20, 30, 40),
            new PointZM(50, 60, 70, 80)
        ];

        $mp = new MultiPointZM($points);

        $expected = [
            'type' => 'MultiPoint',
            'coordinates' => [
                [10, 20, 30, 40],
                [50, 60, 70, 80]
            ]
        ];

        $this->assertEquals($expected, $mp->toGeoJSON());
    }

    public function testCreateFromGeoJSON()
    {
        $geoJson = [
            'type' => 'MultiPoint',
            'coordinates' => [
                [1.1, 2.2, 3.3, 4.4],
                [5.5, 6.6, 7.7, 8.8]
            ]
        ];

        $mp = MultiPointZM::createFromGeoJSON($geoJson, 4326);

        $this->assertInstanceOf(MultiPointZM::class, $mp);
        $this->assertCount(2, $mp->getPoints());
        $this->assertSame(4326, $mp->getPoints()[0]->getSRID());
        $this->assertSame(1.1, $mp->getPoints()[0]->getX());
        $this->assertSame(8.8, $mp->getPoints()[1]->getM());
    }

    public function testCreateFromEWKT()
    {
        $ewkt = 'SRID=4326;MULTIPOINT ZM((1 2 3 4),(5 6 7 8))';
        $mp = MultiPointZM::createFromGeoEWKTString($ewkt);

        $this->assertInstanceOf(MultiPointZM::class, $mp);
        $this->assertCount(2, $mp->getPoints());
        $this->assertEquals(1, $mp->getPoints()[0]->getX());
        $this->assertEquals(8, $mp->getPoints()[1]->getM());
    }

    public function testEmptyPointsThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MultiPointZM([]);
    }

    public function testInvalidGeoJSONThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        MultiPointZM::createFromGeoJSON(['type' => 'MultiPoint', 'coordinates' => [[1,2,3]]]);
    }

    public function testInvalidEWKTThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        MultiPointZM::createFromGeoEWKTString('SRID=4326;MULTIPOINT ZM((1 2 3))');
    }
}