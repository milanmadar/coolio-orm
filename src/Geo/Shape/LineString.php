<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class LineString extends Geometry implements HasStartEndPointInterface
{
    /** @var array<Point> */
    private array $points;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return LineString
     */
    public static function createFromGeoJSONData(array $jsonData, ?int $srid = null): LineString
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
            $points[] = new Point((float)$coords[0], (float)$coords[1], $srid);
        }

        return new LineString($points, $srid);
    }

    /**
     * @param string $ewktString
     * @return LineString
     */
    public static function createFromGeoEWKTString(string $ewktString): LineString
    {
        // Parse the EWKT string, expected format: SRID=<srid>;LINESTRING(<x1> <y1>, <x2> <y2>, ...)
        if (strpos($ewktString, 'LINESTRING') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected LINESTRING type.');
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

        // Validate and extract the LINESTRING coordinates
        $geometryPart = str_replace('((', '(', $geometryPart);
        $geometryPart = str_replace('))', ')', $geometryPart);
        preg_match('/LINESTRING\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid LINESTRING format in EWKT.');
        }

        $pointsData = explode(',', $matches[1]);
        $points = [];

        foreach ($pointsData as $pointData) {
            $coords = array_map('trim', explode(' ', $pointData));
            if (count($coords) !== 2) {
                throw new \InvalidArgumentException('Each point must have exactly 2 coordinates.');
            }

            $points[] = new Point((float) $coords[0], (float) $coords[1], $srid);
        }

        return new LineString($points, $srid);
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

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $pointStrings = array_map(
            fn(Point $p) => sprintf('%s %s', $p->getX(), $p->getY()),
            $this->points
        );

        return 'LINESTRING(' . implode(',', $pointStrings) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'LineString',
            'coordinates' => $this->points
        ];
    }

    public function getStartPoint(): Point
    {
        return $this->points[0];
    }

    public function getEndPoint(): Point
    {
        return $this->points[count($this->points) - 1];
    }
}