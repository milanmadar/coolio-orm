<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class LineString extends Geometry
{
    /** @var array<Point> */
    private array $points;

    public static function createFromGeoJSONData(array $jsonData, ?int $srid = null): static
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'LineString' ||
            !is_array($jsonData['coordinates']) ||
            count($jsonData['coordinates']) < 2
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for LineString');
        }

        $points = [];

        foreach ($jsonData['coordinates'] as $coords) {
            if (!is_array($coords) || count($coords) !== 2) {
                throw new \InvalidArgumentException('Invalid coordinate in LineString');
            }
            $points[] = new Point((float)$coords[0], (float)[1], $srid);
        }

        return new static($points, $srid);
    }

    /**
     * @param array<Point> $points
     * @param int|null $srid
     */
    public function __construct(array $points, int|null $srid = null)
    {
        parent::__construct($srid);
        if(count($points) < 2) {
            throw new \InvalidArgumentException("A LineString must have at least two points.");
        }
        $this->points = $points;
    }

    /**
     * @return array<Point>
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    /**
     * @param array<Point> $points
     * @return $this
     */
    public function setPoints(array $points): self
    {
        if(count($points) < 2) {
            throw new \InvalidArgumentException("A LineString must have at least two points.");
        }
        $this->points = $points;
        return $this;
    }

    public function toWKT(): string
    {
        $pointStrings = array_map(
            fn(Point $p) => sprintf('%F %F', $p->getX(), $p->getY()),
            $this->points
        );

        return 'LINESTRING(' . implode(', ', $pointStrings) . ')';
    }

    public function toGeoJSON(): array
    {
        return [
            'type' => 'LineString',
            'coordinates' => $this->points
        ];
    }
}