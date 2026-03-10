<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\AbstractShape;
use Milanmadar\CoolioORM\Geo\ShapeZ\AbstractShapeZ;

abstract class AbstractShapeZM extends AbstractShape
{
    /**
     * @param array<string, mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return AbstractShapeZ
     */
    abstract public static function createFromGeoJSON(array $jsonData, int|null $srid = null): AbstractShapeZM;

    /**
     * @param string $ewktString
     * @return AbstractShapeZ
     */
    abstract public static function createFromGeoEWKTString(string $ewktString): AbstractShapeZM;
}