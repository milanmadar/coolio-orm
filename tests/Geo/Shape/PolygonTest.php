<?php

namespace Geo\Shape;

use Milanmadar\CoolioORM\Geo\Shape;
use PHPUnit\Framework\TestCase;

class PolygonTest extends TestCase
{
    public function testCreateValid()
    {
        $line = new Shape\LineString([
            new Shape\Point(0, 0),
            new Shape\Point(0, 1),
            new Shape\Point(1, 1),
            new Shape\Point(0, 0),
        ]);
        new Shape\Polygon([$line]);
        $this->assertEquals(1, 1);

        $samePt = new Shape\Point(0, 0);
        $line = new Shape\LineString([
            $samePt,
            new Shape\Point(0, 1),
            new Shape\Point(1, 1),
            $samePt,
        ]);
        new Shape\Polygon([$line]);
        $this->assertEquals(1, 1);
    }

    public function testCreateInvalid_NotEnoughPoints()
    {
        $this->expectException(\InvalidArgumentException::class);

        $line = new Shape\LineString([
            new Shape\Point(0, 0),
            new Shape\Point(1, 1),
            new Shape\Point(0, 0),
        ]);
        new Shape\Polygon([$line]);
    }

    public function testCreateInvalid_NotClosingPoints()
    {
        $this->expectException(\InvalidArgumentException::class);

        $line = new Shape\LineString([
            new Shape\Point(0, 0),
            new Shape\Point(0, 1),
            new Shape\Point(1, 1),
            new Shape\Point(0, 0.1),
        ]);
        new Shape\Polygon([$line]);
    }

}