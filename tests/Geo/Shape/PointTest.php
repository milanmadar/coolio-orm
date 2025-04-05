<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape\Point;
use PHPUnit\Framework\TestCase;

class PointTest extends TestCase
{
    public function testCreate()
    {
        $shape = new Point(1.123456, 2.23456);
        $this->assertEquals($_ENV['GEO_DEFAULT_SRID'], $shape->getSRID());

        $shape = new Point(1.123456, 2.23456, 1);
        $this->assertEquals(1, $shape->getSRID());
    }

    public function testSTGeomFromEWKT()
    {
        $point = new Point(1.0, 2.0, 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POINT(1 2)')";
        $this->assertSame($expected, $point->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTWithDecimals()
    {
        $point = new Point(12.3456, 78.9101, 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;POINT(12.3456 78.9101)')";
        $this->assertSame($expected, $point->ST_GeomFromEWKT());
    }

}