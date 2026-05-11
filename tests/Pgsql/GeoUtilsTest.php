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
}