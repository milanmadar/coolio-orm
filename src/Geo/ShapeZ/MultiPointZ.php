<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class MultiPointZ extends GeometryZ
{
    /** @var array<PointZ> */
    private array $points;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return MultiPointZ
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): MultiPointZ
    {
        if (!isset($srid)) {
            $srid = $_ENV['GEO_DEFAULT_SRID'];
        }

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'MultiPoint' ||
            !is_array($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for MultiPointZ');
        }

        $points = [];

        foreach ($jsonData['coordinates'] as $coords) {
            if (!is_array($coords) || count($coords) !== 3) {
                throw new \InvalidArgumentException('Invalid coordinate in MultiPointZ');
            }
            $points[] = new PointZ((float)$coords[0], (float)$coords[1], (float)$coords[2], $srid);
        }

        return new MultiPointZ($points, $srid);
    }

    /**
     * @param string $ewktString
     * @return MultiPointZ
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiPointZ
    {
        // Parse the EWKT string, expected format: SRID=<srid>;MULTIPOINT Z(<x1> <y1> <z1>, <x2> <y2> <z2>, ...)
        //if (strpos($ewktString, 'MULTIPOINT Z') === false) {
        if (strpos($ewktString, 'MULTIPOINT') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected MULTIPOINT Z type.');
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

        // Validate and extract the MULTIPOINT Z coordinates
        preg_match('/MULTIPOINT ?Z?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid MULTIPOINT Z format in EWKT.');
        }

        $pointsData = explode(',', $matches[1]);
        $points = [];

        foreach ($pointsData as $pointData) {
            $coords = array_map('trim', explode(' ', $pointData));
            if (count($coords) !== 3) {
                throw new \InvalidArgumentException('Each point must have exactly 3 coordinates.');
            }

            $points[] = new PointZ((float) $coords[0], (float) $coords[1], (float) $coords[2], $srid);
        }

        return new MultiPointZ($points, $srid);
    }

    /**
     * @param array<PointZ> $points
     * @param int|null $srid
     */
    public function __construct(array $points, int|null $srid = null)
    {
        if (empty($points)) {
            throw new \InvalidArgumentException('MultiPointZ must contain at least one PointZ.');
        }

        parent::__construct($srid);
        $this->points = $points;
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
    public function setPoints(array $points): MultiPointZ
    {
        if (empty($points)) {
            throw new \InvalidArgumentException('MultiPointZ must contain at least one PointZ.');
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
            fn(PointZ $p) => sprintf('%s %s %s', $p->getX(), $p->getY(), $p->getZ()),
            $this->points
        );

        return 'MULTIPOINT Z(' . implode(',', $pointStrings) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'MultiPoint',
            'coordinates' => array_map(
                fn(PointZ $p) => [$p->getX(), $p->getY(), $p->getZ()],
                $this->points
            )
        ];
    }
}
