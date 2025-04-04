<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class Point extends Geometry
{
    private float $x;
    private float $y;

    /**
     * @param array $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return Point
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): static
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'Point' ||
            !is_array($jsonData['coordinates']) ||
            count($jsonData['coordinates']) !== 2
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for Point');
        }

        return new self($jsonData['coordinates'][0], $jsonData['coordinates'][1], $srid);
    }

    public function __construct(float $x, float $y, int|null $srid = null)
    {
        parent::__construct($srid);
        $this->x = $x;
        $this->y = $y;
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function setX(float $x): self
    {
        $this->x = $x;
        return $this;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function setY(float $y): self
    {
        $this->y = $y;
        return $this;
    }

    public function getCoordinates(): array
    {
        return [$this->x, $this->y];
    }

    public function toWKT(): string
    {
        return sprintf('POINT(%F %F)', $this->x, $this->y);
    }

    public function toGeoJSON(): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$this->x, $this->y],
        ];
    }
}