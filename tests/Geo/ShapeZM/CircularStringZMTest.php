<?php

namespace Geo\ShapeZM;


use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\CircularStringZM;
use PHPUnit\Framework\TestCase;

class CircularStringZMTest extends TestCase
{
    public function testCircularStringZMFromEWKT()
    {
        $ewkt = 'SRID=4326;CIRCULARSTRING ZM(0 0 0 100, 1 1 1 100, 2 0 2 100, 3 1 3 100, 4 0 4 100)';

        $cs = CircularStringZM::createFromGeoEWKTString($ewkt);

        $this->assertCount(5, $cs->getPoints());

        // Check first and last points
        $first = $cs->getStartPointZM();
        $last = $cs->getEndPointZM();

        $this->assertEquals([0, 0, 0, 100], [$first->getX(), $first->getY(), $first->getZ(), $first->getM()]);
        $this->assertEquals([4, 0, 4, 100], [$last->getX(), $last->getY(), $last->getZ(), $last->getM()]);

        // Check WKT output
        $expectedWKT = 'CIRCULARSTRING ZM(0 0 0 100,1 1 1 100,2 0 2 100,3 1 3 100,4 0 4 100)';
        $this->assertSame($expectedWKT, $cs->toWKT());
    }

    public function testCircularStringZMValidationFails()
    {
        // Even number of points should throw
        $points = [
            new PointZM(0,0,0,1),
            new PointZM(1,1,1,1),
            new PointZM(2,2,2,1),
            new PointZM(3,3,3,1)
        ];

        $this->expectException(\InvalidArgumentException::class);
        new CircularStringZM($points);
    }
}