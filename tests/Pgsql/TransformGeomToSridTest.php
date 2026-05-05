<?php

namespace Pgsql;

use PHPUnit\Framework\TestCase;
use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\Geo;

class TransformGeomToSridTest extends TestCase
{

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
    }

    public function testIt()
    {
        $orm = \Milanmadar\CoolioORM\ORM::instance();
        $line = new Geo\ShapeZ\LineStringZ([
            new Geo\ShapeZ\PointZ(1, 2, 3),
            new Geo\ShapeZ\PointZ(4, 5, 6),
            new Geo\ShapeZ\PointZ(7, 8, 9)
        ], 4326);
        $lineRegional = $orm->transformGeomToSrid($line, 32633, $_ENV['DB_POSTGRES_DB1']);
        $this->assertInstanceOf(Geo\ShapeZ\LineStringZ::class, $lineRegional);
        $this->assertEquals(32633, $lineRegional->getSRID());
    }

}