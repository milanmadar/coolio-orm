<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

abstract class Geometry
{
    protected int $srid;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return Geometry
     */
    abstract public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): static;

    /**
     * @param string $ewktString
     * @return Geometry
     */
    abstract public static function createFromGeoEWKTString(string $ewktString): static;

    public function __construct(int|null $srid = null)
    {
        $this->srid = $srid ?? $_ENV['GEO_DEFAULT_SRID'];
    }

    public function getSRID(): int
    {
        return $this->srid;
    }

    abstract public function toWKT(): string;

    abstract public function toGeoJSON(): array;

    public function toEWKT(): string
    {
        return sprintf('SRID=%d;%s', $this->srid, $this->toWKT());
    }

    public function __toString(): string
    {
        return $this->toWKT();
    }
}