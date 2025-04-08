<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

use Milanmadar\CoolioORM\Geo\AbstractShape;

abstract class AbstractShapeZ extends AbstractShape
{
    protected int $srid;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return AbstractShapeZ
     */
    abstract public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): AbstractShapeZ;

    /**
     * @param string $ewktString
     * @return AbstractShapeZ
     */
    abstract public static function createFromGeoEWKTString(string $ewktString): AbstractShapeZ;
}