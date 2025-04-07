<?php

namespace Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\MultiLineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use PHPUnit\Framework\TestCase;

class MultiLineStringZTest extends TestCase
{
    public function testSTGeomFromEWKTSimple()
    {
        // Simple case: A MultiLineString with two lines
        $line1 = new LineStringZ([
            new PointZ(1, 1, 1),
            new PointZ(2, 2, 2)
        ], 4326);

        $line2 = new LineStringZ([
            new PointZ(3, 3, 3),
            new PointZ(4, 4, 4)
        ], 4326);

        $multiLineString = new MultiLineStringZ([$line1, $line2], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTILINESTRING Z((1 1 1,2 2 2),(3 3 3,4 4 4))')";
        $this->assertSame($expected, $multiLineString->ST_GeomFromEWKT());
    }

    public function testSTGeomFromEWKTComplex()
    {
        // Complex case: A MultiLineString with multiple lines
        $line1 = new LineStringZ([
            new PointZ(1, 1, 1),
            new PointZ(2, 2, 1),
            new PointZ(3, 3, 1)
        ], 4326);

        $line2 = new LineStringZ([
            new PointZ(4, 4, 1),
            new PointZ(5, 5, 1)
        ], 4326);

        $line3 = new LineStringZ([
            new PointZ(6, 6, 1),
            new PointZ(7, 7, 1),
            new PointZ(8, 8, 1)
        ], 4326);

        $multiLineString = new MultiLineStringZ([$line1, $line2, $line3], 4326);

        $expected = "ST_GeomFromEWKT('SRID=4326;MULTILINESTRING Z((1 1 1,2 2 1,3 3 1),(4 4 1,5 5 1),(6 6 1,7 7 1,8 8 1))')";
        $this->assertSame($expected, $multiLineString->ST_GeomFromEWKT());
    }

}