<?php

namespace Pgsql;

use PHPUnit\Framework\TestCase;
use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\Geo;

class GeoUtilsTest extends TestCase
{

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
    }

    public function testTransformGeomToSrid()
    {
        $db = \Milanmadar\CoolioORM\ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);

        $line = new Geo\ShapeZ\LineStringZ([
            new Geo\ShapeZ\PointZ(1, 2, 3),
            new Geo\ShapeZ\PointZ(4, 5, 6),
            new Geo\ShapeZ\PointZ(7, 8, 9)
        ], 4326);


        $lineRegional = Geo\Utils::transformGeomToSrid($line, 32633, $db);
        $this->assertInstanceOf(Geo\ShapeZ\LineStringZ::class, $lineRegional);
        $this->assertEquals(32633, $lineRegional->getSRID());
    }

    public function testGetDistanceInMeters()
    {
        $db = \Milanmadar\CoolioORM\ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);

        $p1 = new Geo\Shape2D\Point(448250, 5302850, 32631);
        $p2 = new Geo\Shape2D\Point(448350, 5302850, 32631);

        $dist = Geo\Utils::getDistanceInMeters($p1, $p2, $db, 0);
        $this->assertEquals(100, $dist);
    }

    public function testGetClosestPoint2D()
    {
        $db = \Milanmadar\CoolioORM\ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);

        $srid = 32631;
        $line = new Geo\Shape2D\LineString([
            new Geo\Shape2D\Point(0, 0, $srid),
            new Geo\Shape2D\Point(100, 100, $srid),
        ]);
        $point = new Geo\Shape2D\Point(0, 100, $srid);

        $snappedPoint = Geo\Utils::getClosestPoint($line, $point, $db);
        $this->assertEquals(50, (int)$snappedPoint->getX());
        $this->assertEquals(50, (int)$snappedPoint->gety());
        $this->assertEquals($srid, $snappedPoint->getSRID());
    }

    public function testgetLength_fromPointInLine_tillEndOfLine_InMeter_2d()
    {
        $db = \Milanmadar\CoolioORM\ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);

        $srid = 4326;
        $point = new Geo\Shape2D\Point(5, 5, $srid);
        $line = new Geo\Shape2D\LineString([
            new Geo\Shape2D\Point(0, 0, $srid),
            new Geo\Shape2D\Point(10, 10, $srid),
        ]);

        $length = Geo\Utils::getLength_fromPointInLine_tillEndOfLine_InMeter($line, $point, 'end', $db, 0);
        $this->assertEquals(781106, $length);
    }

    public function testGetClosestPointZM()
    {
        $db = \Milanmadar\CoolioORM\ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);

        $srid = 32631;
        $line = new Geo\ShapeZM\LineStringZM([
            new Geo\ShapeZM\PointZM(0, 0, 0, 1111, $srid),
            new Geo\ShapeZM\PointZM(100, 100, 100, 2222, $srid),
        ]);
        $point = new Geo\ShapeZM\PointZM(0, 100, 100, 3333, $srid);

        $snappedPoint = Geo\Utils::getClosestPoint($line, $point, $db);
        $this->assertEquals(50, (int)$snappedPoint->getX());
        $this->assertEquals(50, (int)$snappedPoint->getY());
        $this->assertEquals(50, (int)$snappedPoint->getZ());
        $this->assertEquals($srid, $snappedPoint->getSRID());
    }

    public function test3DTo2D()
    {
        $ewktIn = "SRID=4326;GEOMETRYCOLLECTION Z (" .
            "POINT Z (10 20 30), " .
            "LINESTRING Z (1 2 3, 5 6 7), " .
            "POLYGON Z ((0 0 0, 10 0 0, 10 10 0, 0 0 0), (2 2 0, 4 2 0, 4 4 0, 2 2 0))" .
            ")";
        $geomIn = Geo\Shape2D3D4DFactory::createFromGeoEWKTString($ewktIn);

        $ewktOut = 'SRID=4326;GEOMETRYCOLLECTION(POINT(10.00000000 20.00000000),LINESTRING(1.00000000 2.00000000,5.00000000 6.00000000),POLYGON((0.00000000 0.00000000,10.00000000 0.00000000,10.00000000 10.00000000,0.00000000 0.00000000),(2.00000000 2.00000000,4.00000000 4.00000000,4.00000000 2.00000000,2.00000000 2.00000000)))';

        $geomOut = Geo\Utils::to2D($geomIn);

        $this->assertEquals($ewktOut, $geomOut->toEWKT());
    }

    public function test4DTo3D()
    {
        $ewktIn = "SRID=4326;GEOMETRYCOLLECTION ZM (" .
            "POINT ZM (10 20 30 40), " .
            "LINESTRING ZM (1 2 3 4, 5 6 7 8), " .
            "POLYGON ZM ((0 0 0 0, 10 0 0 0, 10 10 0 0, 0 0 0 0), (2 2 0 0, 4 2 0 0, 4 4 0 0, 2 2 0 0))" .
            ")";
        $geomIn = Geo\Shape2D3D4DFactory::createFromGeoEWKTString($ewktIn);

        $ewktOut = 'SRID=4326;GEOMETRYCOLLECTION Z(POINT Z(10.00000000 20.00000000 30.00000000),LINESTRING Z(1.00000000 2.00000000 3.00000000,5.00000000 6.00000000 7.00000000),POLYGON Z((0.00000000 0.00000000 0.00000000,10.00000000 0.00000000 0.00000000,10.00000000 10.00000000 0.00000000,0.00000000 0.00000000 0.00000000),(2.00000000 2.00000000 0.00000000,4.00000000 4.00000000 0.00000000,4.00000000 2.00000000 0.00000000,2.00000000 2.00000000 0.00000000)))';

        $geomOut = Geo\Utils::to3D($geomIn);

        $this->assertEquals($ewktOut, $geomOut->toEWKT());
    }

    public function test4DTo2D()
    {
        $ewktIn = "SRID=4326;GEOMETRYCOLLECTION ZM (" .
            "POINT ZM (10 20 30 40), " .
            "LINESTRING ZM (1 2 3 4, 5 6 7 8), " .
            "POLYGON ZM ((0 0 0 0, 10 0 0 0, 10 10 0 0, 0 0 0 0), (2 2 0 0, 4 2 0 0, 4 4 0 0, 2 2 0 0))" .
            ")";
        $geomIn = Geo\Shape2D3D4DFactory::createFromGeoEWKTString($ewktIn);

        $ewktOut = 'SRID=4326;GEOMETRYCOLLECTION(POINT(10.00000000 20.00000000),LINESTRING(1.00000000 2.00000000,5.00000000 6.00000000),POLYGON((0.00000000 0.00000000,10.00000000 0.00000000,10.00000000 10.00000000,0.00000000 0.00000000),(2.00000000 2.00000000,4.00000000 4.00000000,4.00000000 2.00000000,2.00000000 2.00000000)))';

        $geomOut = Geo\Utils::to2D($geomIn);

        $this->assertEquals($ewktOut, $geomOut->toEWKT());
    }

    public function test2DTo3D()
    {
        $ewktIn = "SRID=4326;GEOMETRYCOLLECTION (" .
            "POINT (10 20), " .
            "LINESTRING (1 2, 5 6), " .
            "POLYGON ((0 0, 10 0, 10 10, 0 0), (2 2, 4 2, 4 4, 2 2))" .
            ")";
        $geomIn = Geo\Shape2D3D4DFactory::createFromGeoEWKTString($ewktIn);

        $ewktOut = 'SRID=4326;GEOMETRYCOLLECTION Z(POINT Z(10.00000000 20.00000000 100.00000000),LINESTRING Z(1.00000000 2.00000000 100.00000000,5.00000000 6.00000000 100.00000000),POLYGON Z((0.00000000 0.00000000 100.00000000,10.00000000 0.00000000 100.00000000,10.00000000 10.00000000 100.00000000,0.00000000 0.00000000 100.00000000),(2.00000000 2.00000000 100.00000000,4.00000000 4.00000000 100.00000000,4.00000000 2.00000000 100.00000000,2.00000000 2.00000000 100.00000000)))';

        $geomOut = Geo\Utils::to3D($geomIn, 100);

        $this->assertEquals($ewktOut, $geomOut->toEWKT());
    }

    public function test3DTo4D()
    {
        $ewktIn = "SRID=4326;GEOMETRYCOLLECTION Z (" .
            "POINT Z (10 20 30), " .
            "LINESTRING Z (1 2 3, 5 6 7), " .
            "POLYGON Z ((0 0 0, 10 0 0, 10 10 0, 0 0 0), (2 2 0, 4 2 0, 4 4 0, 2 2 0))" .
            ")";
        $geomIn = Geo\Shape2D3D4DFactory::createFromGeoEWKTString($ewktIn);

        $ewktOut = 'SRID=4326;GEOMETRYCOLLECTION ZM(POINT ZM(10.00000000 20.00000000 30.00000000 100.00000000),LINESTRING ZM(1.00000000 2.00000000 3.00000000 100.00000000,5.00000000 6.00000000 7.00000000 100.00000000),POLYGON ZM((0.00000000 0.00000000 0.00000000 100.00000000,10.00000000 0.00000000 0.00000000 100.00000000,10.00000000 10.00000000 0.00000000 100.00000000,0.00000000 0.00000000 0.00000000 100.00000000),(2.00000000 2.00000000 0.00000000 100.00000000,4.00000000 4.00000000 0.00000000 100.00000000,4.00000000 2.00000000 0.00000000 100.00000000,2.00000000 2.00000000 0.00000000 100.00000000)))';

        $geomOut = Geo\Utils::to4D($geomIn, 100, 100);

        $this->assertEquals($ewktOut, $geomOut->toEWKT());
    }

    public function test2DTo4D()
    {
        $ewktIn = "SRID=4326;GEOMETRYCOLLECTION (" .
            "POINT (10 20), " .
            "LINESTRING (1 2, 5 6), " .
            "POLYGON ((0 0, 10 0, 10 10, 0 0), (2 2, 4 2, 4 4, 2 2))" .
            ")";
        $geomIn = Geo\Shape2D3D4DFactory::createFromGeoEWKTString($ewktIn);

        $ewktOut = 'SRID=4326;GEOMETRYCOLLECTION ZM(POINT ZM(10.00000000 20.00000000 50.00000000 100.00000000),LINESTRING ZM(1.00000000 2.00000000 50.00000000 100.00000000,5.00000000 6.00000000 50.00000000 100.00000000),POLYGON ZM((0.00000000 0.00000000 50.00000000 100.00000000,10.00000000 0.00000000 50.00000000 100.00000000,10.00000000 10.00000000 50.00000000 100.00000000,0.00000000 0.00000000 50.00000000 100.00000000),(2.00000000 2.00000000 50.00000000 100.00000000,4.00000000 4.00000000 50.00000000 100.00000000,4.00000000 2.00000000 50.00000000 100.00000000,2.00000000 2.00000000 50.00000000 100.00000000)))';

        $geomOut = Geo\Utils::to4D($geomIn, 50, 100);

        $this->assertEquals($ewktOut, $geomOut->toEWKT());
    }
}