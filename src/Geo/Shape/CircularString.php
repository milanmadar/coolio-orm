<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class CircularString extends Geometry
{
    /** @var array<Point> */
    private  array $points;

    /**
     * @param array<mixed> $points
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return CircularString
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): static
    {
        // GeoJSON does not support CircularString by spec
        throw new \RuntimeException('GeoJSON does not support CircularString. Use EWKT instead.');
    }

    /**
     * @param string $ewktString
     * @return CircularString
     */
    public static function createFromGeoEWKTString(string $ewktString): static
    {
        if (!str_contains($ewktString, 'CIRCULARSTRING')) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected CIRCULARSTRING type.');
        }

        // Extract the SRID and the WKT string
        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) != 2) {
            throw new \InvalidArgumentException('Invalid EWKT string, could not find SRID and geometry parts.');
        }

        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        // Extract SRID value
        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);

        // Validate and extract the CIRCULARSTRING points
        preg_match('/CIRCULARSTRING\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid CIRCULARSTRING format in EWKT.');
        }

        $pointsData = explode(',', $matches[1]);
        $points = [];

        foreach ($pointsData as $pointData) {
            $coords = array_map('trim', explode(' ', $pointData));
            if (count($coords) !== 2) {
                throw new \InvalidArgumentException('Each point must contain exactly 2 coordinates.');
            }

            $points[] = new Point((float)$coords[0], (float)$coords[1], $srid);
        }

        return new static($points, $srid);
    }

    /**
     * @param array<Point> $points
     * @param int|null $srid
     */
    public function __construct(array $points, int|null $srid = null)
    {
        $this->_validatePoints($points);
        parent::__construct($srid);
        $this->points = $points;
    }

    public function toWKT(): string
    {
        $pointStrings = array_map(
            fn(Point $p) => sprintf('%F %F', $p->getX(), $p->getY()),
            $this->points
        );

        return 'CIRCULARSTRING(' . implode(', ', $pointStrings) . ')';
    }

    public function toGeoJSON(): array
    {
        throw new \RuntimeException('GeoJSON does not support CircularString.');
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
        $this->_validatePoints($points);
        $this->points = $points;
        return $this;
    }

    /**
     * @param array<Point> $points
     * @throws \InvalidArgumentException
     */
    private function _validatePoints(array $points): void
    {
        if (count($points) < 3 || count($points) % 2 === 0) {
            throw new \InvalidArgumentException('A CircularString must have an odd number of points ≥ 3.');
        }
    }
}
