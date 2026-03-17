<?php

namespace Geo;

use PHPUnit\Framework\TestCase;
use Milanmadar\CoolioORM\Geo\Utils;

class UtilsTest extends TestCase
{
    public function testGetUtmSridFromWGS()
    {
        $wgsLat = 47.50886240725931;
        $wgsLon = 19.097313859607482;
        $regionalSrid = Utils::getUtmSridFromWGS($wgsLon, $wgsLat);
        $this->assertEquals(32634, $regionalSrid);
    }
}