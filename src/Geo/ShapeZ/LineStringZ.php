<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class LineStringZ extends GeometryZ implements HasStartEndPointZInterface
{
    private array $points;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return LineStringZ
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): LineStringZ
    {
        if (!isset($srid)) {
            $srid = $_ENV['GEO_DEFAULT_SRID'];
        }

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'LineString' ||
            !is_array($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for LineString');
        }

        $coordinates = [];
        foreach ($jsonData['coordinates'] as $coord) {
            if (count($coord) !== 3) {
                throw new \InvalidArgumentException('LineStringZ must have 3 coordinates per point.');
            }
            $coordinates[] = new PointZ($coord[0], $coord[1], $coord[2], $srid);
        }

        return new LineStringZ($coordinates, $srid);
    }

    /**
     * @param string $ewktString
     * @return LineStringZ
     */
    public static function createFromGeoEWKTString(string $ewktString): LineStringZ
    {
        // Parse the EWKT string, expected format: SRID=<srid>;LINESTRING Z(<x1> <y1> <z1>, <x2> <y2> <z2>, ...)
        //if (strpos($ewktString, 'LINESTRING Z') === false) {
        if (strpos($ewktString, 'LINESTRING') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected LINESTRING Z type.');
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

        $geometryPart = str_replace('((', '(', $geometryPart);
        $geometryPart = str_replace('))', ')', $geometryPart);
        preg_match('/LINESTRING ?Z?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid LINESTRING Z format in EWKT.');
        }

        $coords = array_map('trim', explode(',', $matches[1]));
        $points = [];
        foreach ($coords as $coord) {
            $pointData = array_map('trim', explode(' ', $coord));
            if (count($pointData) !== 3) {
                throw new \InvalidArgumentException('Each point in a LINESTRING Z must have 3 coordinates.');
            }
            $points[] = new PointZ((float) $pointData[0], (float) $pointData[1], (float) $pointData[2], $srid);
        }

        return new LineStringZ($points, $srid);
    }

    public function __construct(array $coordinates, int|null $srid = null)
    {
        parent::__construct($srid);
        $this->points = $coordinates;
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
        if(count($points) < 2) {
            throw new \InvalidArgumentException("A LineString must have at least two points.");
        }
        $this->points = $points;
        return $this;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $coordinateStrings = array_map(function (PointZ $point) {
            return sprintf('%s %s %s', $point->getX(), $point->getY(), $point->getZ());
        }, $this->points);

        return sprintf('LINESTRING Z(%s)', implode(',', $coordinateStrings));
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        $coordinates = array_map(function (PointZ $point) {
            return [$point->getX(), $point->getY(), $point->getZ()];
        }, $this->points);

        return [
            'type' => 'LineString',
            'coordinates' => $coordinates,
        ];
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