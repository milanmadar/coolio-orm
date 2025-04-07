<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class CircularStringZ extends GeometryZ implements HasStartEndPointZInterface
{
    /** @var array<PointZ> */
    private array $points;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID
     * @return CircularStringZ
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): CircularStringZ
    {
        // GeoJSON does not support CircularString, same as 2D
        throw new \RuntimeException('GeoJSON does not support CircularStringZ. Use EWKT instead.');
    }

    /**
     * @param string $ewktString
     * @return CircularStringZ
     */
    public static function createFromGeoEWKTString(string $ewktString): CircularStringZ
    {
        //if (!str_contains($ewktString, 'CIRCULARSTRINGZ')) {
        if (!str_contains($ewktString, 'CIRCULARSTRING')) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected CIRCULARSTRINGZ type.');
        }

        // Extract the SRID and WKT string
        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) !== 2) {
            throw new \InvalidArgumentException('Invalid EWKT string, could not find SRID and geometry parts.');
        }

        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);

        // Validate and extract the CIRCULARSTRINGZ points
        preg_match('/CIRCULARSTRING ?Z?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid CIRCULARSTRINGZ format in EWKT.');
        }

        $pointsData = explode(',', $matches[1]);
        $points = [];

        foreach ($pointsData as $pointData) {
            $coords = array_map('trim', preg_split('/\s+/', $pointData));
            if (count($coords) !== 3) {
                throw new \InvalidArgumentException('Each point must contain exactly 3 coordinates.');
            }

            $points[] = new PointZ(
                (float) $coords[0],
                (float) $coords[1],
                (float) $coords[2],
                $srid
            );
        }

        return new CircularStringZ($points, $srid);
    }

    /**
     * @param array<PointZ> $points
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
            fn(PointZ $p) => sprintf('%s %s %s', $p->getX(), $p->getY(), $p->getZ()),
            $this->points
        );

        return 'CIRCULARSTRINGZ(' . implode(',', $pointStrings) . ')';
    }

    public function toGeoJSON(): array
    {
        throw new \RuntimeException('GeoJSON does not support CircularStringZ.');
    }

    /**
     * @return array<PointZ>
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    /**
     * @param array<PointZ> $points
     * @return $this
     */
    public function setPoints(array $points): self
    {
        $this->_validatePoints($points);
        $this->points = $points;
        return $this;
    }

    /**
     * @param array<PointZ> $points
     * @throws \InvalidArgumentException
     */
    private function _validatePoints(array $points): void
    {
        if (count($points) < 3 || count($points) % 2 === 0) {
            throw new \InvalidArgumentException('A CircularStringZ must have an odd number of points ≥ 3.');
        }
    }

    public function getStartPointZ(): PointZ
    {
        return $this->points[0];
    }

    public function getEndPointZ(): PointZ
    {
        return $this->points[count($this->points) - 1];
    }
}
