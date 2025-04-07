<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use PHPUnit\Framework\TestCase;

class PointZTest extends TestCase
{
    public function testCreate()
    {
        $shape = new PointZ(1.123456, 2.23456, 3.12345);
        $this->assertEquals($_ENV['GEO_DEFAULT_SRID'], $shape->getSRID());

        $shape = new PointZ(1.123456, 2.23456, 3.12345, 1);
        $this->assertEquals(1, $shape->getSRID());
    }

    public function testSTGeomFromEWKT()
    {
        $point = new PointZ(1.0, 2.0, 3.0, 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POINTZ(1 2 3)')";
        $this->assertSame($expected, $point->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTWithDecimals()
    {
        $point = new PointZ(12.3456, 78.9101, 50.54321, 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POINTZ(12.3456 78.9101 50.54321)')";
        $this->assertSame($expected, $point->ST_GeomFromEWKT());
    }

}