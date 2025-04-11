<?php

namespace Milanmadar\CoolioORM\Geo\Shape2D;

class MultiPoint extends AbstractShape2D
{
    /** @var array<Point> */
    private array $points;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return MultiPoint
     */
    /*public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): MultiPoint
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'MultiPoint' ||
            !is_array($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for MultiPoint');
        }

        $points = [];

        foreach ($jsonData['coordinates'] as $coords) {
            if (!is_array($coords) || count($coords) !== 2) {
                throw new \InvalidArgumentException('Invalid coordinate in MultiPoint');
            }
            $points[] = new Point((float)$coords[0], (float)$coords[1], $srid);
        }

        return new MultiPoint($points, $srid);
    }*/

    /**
     * @param string $ewktString
     * @return MultiPoint
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiPoint
    {
        // Parse the EWKT string, expected format: SRID=<srid>;MULTIPOINT(<x1> <y1>, <x2> <y2>, ...)
        if (strpos($ewktString, 'MULTIPOINT') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected MULTIPOINT type.');
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

        // Validate and extract the MULTIPOINT coordinates
        preg_match('/MULTIPOINT\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid MULTIPOINT format in EWKT.');
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

        return new MultiPoint($points, $srid);
    }

    /**
     * @param array<Point> $points
     * @param int|null $srid
     */
    public function __construct(array $points, int|null $srid = null)
    {
        if (empty($points)) {
            throw new \InvalidArgumentException('MultiPoint must contain at least one Point.');
        }

        parent::__construct($srid);
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
    public function setPoints(array $points): MultiPoint
    {
        if (empty($points)) {
            throw new \InvalidArgumentException('MultiPoint must contain at least one Point.');
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

        return 'MULTIPOINT(' . implode(',', $pointStrings) . ')';
    }

    /*public function toGeoJSON(): array
    {
        return [
            'type' => 'MultiPoint',
            'coordinates' => array_map(
                fn(Point $p) => [$p->getX(), $p->getY()],
                $this->points
            )
        ];
    }*/
}
