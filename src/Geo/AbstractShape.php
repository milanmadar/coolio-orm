<?php

namespace Milanmadar\CoolioORM\Geo;

abstract class AbstractShape
{
    protected int $srid;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return AbstractShape
     */
    //abstract public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): AbstractShape;

    /**
     * @param string $ewktString
     * @return AbstractShape
     */
    abstract public static function createFromGeoEWKTString(string $ewktString): AbstractShape;

    public function __construct(int|null $srid = null)
    {
        $this->srid = $srid ?? $_ENV['GEO_DEFAULT_SRID'];
    }

    public function getSRID(): int
    {
        return $this->srid;
    }

    /**
     * @return string
     */
    abstract public function toWKT(): string;

    //abstract public function toGeoJSON(): array;

    /**
     * @return string
     */
    public function toEWKT(): string
    {
        return sprintf('SRID=%d;%s', $this->srid, $this->toWKT());
    }

    /**
     * @return string
     */
    public function ST_GeomFromEWKT(): string
    {
        return sprintf('ST_GeomFromEWKT(\'%s\')', $this->toEWKT());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->ST_GeomFromEWKT();
    }
}