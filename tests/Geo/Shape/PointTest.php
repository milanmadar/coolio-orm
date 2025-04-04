<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape;
use PHPUnit\Framework\TestCase;

class PointTest extends TestCase
{
    public function testCreate()
    {
        $shape = new Shape\Point(1.123456, 2.23456);
        $this->assertEquals($_ENV['GEO_DEFAULT_SRID'], $shape->getSRID());

        $shape = new Shape\Point(1.123456, 2.23456, 1);
        $this->assertEquals(1, $shape->getSRID());
    }

}