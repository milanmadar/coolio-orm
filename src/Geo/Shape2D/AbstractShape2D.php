<?php

namespace Milanmadar\CoolioORM\Geo\Shape2D;

use Milanmadar\CoolioORM\Geo\AbstractShape;

abstract class AbstractShape2D extends AbstractShape
{
    protected int $srid;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return AbstractShape2D
     */
    //abstract public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): AbstractShape2D;

    /**
     * @param string $ewktString
     * @return AbstractShape2D
     */
    abstract public static function createFromGeoEWKTString(string $ewktString): AbstractShape2D;
}