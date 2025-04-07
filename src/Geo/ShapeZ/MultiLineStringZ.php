<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class MultiLineStringZ extends GeometryZ
{
    /** @var array<LineStringZ> */
    private array $lineStrings;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return MultiLineStringZ
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): MultiLineStringZ
    {
        if (!isset($srid)) {
            $srid = $_ENV['GEO_DEFAULT_SRID'];
        }

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'MultiLineString' ||
            !is_array($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for MultiLineStringZ');
        }

        $lineStrings = [];

        foreach ($jsonData['coordinates'] as $lineCoords) {
            if (!is_array($lineCoords) || count($lineCoords) < 2) {
                throw new \InvalidArgumentException('Each LineString must have at least 2 coordinates.');
            }

            $points = [];

            foreach ($lineCoords as $coords) {
                if (!is_array($coords) || count($coords) !== 3) {
                    throw new \InvalidArgumentException('Invalid coordinate in MultiLineStringZ');
                }
                $points[] = new PointZ((float)$coords[0], (float)$coords[1], (float)$coords[2], $srid);
            }

            $lineStrings[] = new LineStringZ($points, $srid);
        }

        return new MultiLineStringZ($lineStrings, $srid);
    }

    /**
     * @param string $ewktString
     * @return MultiLineStringZ
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiLineStringZ
    {
        // Parse the EWKT string, expected format: SRID=<srid>;MULTILINESTRING Z((<x1> <y1> <z1>, <x2> <y2> <z2>, ...), ...)
        //if (strpos($ewktString, 'MULTILINESTRING Z') === false) {
        if (strpos($ewktString, 'MULTILINESTRING') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected MULTILINESTRING Z type.');
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

        // Validate and extract the MULTILINESTRING Z coordinates
        preg_match('/MULTILINESTRING ?Z?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid MULTILINESTRING Z format in EWKT.');
        }

        $lineStringsData = explode('),', $matches[1]);
        $lineStrings = [];

        foreach ($lineStringsData as $lineStringData) {
            // Clean up the line string (remove extra spaces and commas)
            $lineStringData = trim($lineStringData);
            if ($lineStringData[0] === '(') {
                $lineStringData = substr($lineStringData, 1);
            }
            if (substr($lineStringData, -1) === ')') {
                $lineStringData = substr($lineStringData, 0, -1);
            }

            $pointsData = explode(',', $lineStringData);
            $points = [];

            foreach ($pointsData as $pointData) {
                $coords = array_map('trim', explode(' ', $pointData));
                if (count($coords) !== 3) {
                    throw new \InvalidArgumentException('Each point in the LineString must have exactly 3 coordinates.');
                }

                $points[] = new PointZ((float) $coords[0], (float) $coords[1], (float) $coords[2], $srid);
            }

            $lineStrings[] = new LineStringZ($points, $srid);
        }

        return new MultiLineStringZ($lineStrings, $srid);
    }

    /**
     * @param array<LineStringZ> $lineStrings
     * @param int|null $srid
     */
    public function __construct(array $lineStrings, int|null $srid = null)
    {
        if (empty($lineStrings)) {
            throw new \InvalidArgumentException('MultiLineStringZ must contain at least one LineStringZ.');
        }

        parent::__construct($srid);
        $this->lineStrings = $lineStrings;
    }

    /**
     * @return array<LineStringZ>
     */
    public function getLineStrings(): array
    {
        return $this->lineStrings;
    }

    /**
     * @param array<LineStringZ> $lineStrings
     * @return $this
     */
    public function setLineStrings(array $lineStrings): MultiLineStringZ
    {
        if (empty($lineStrings)) {
            throw new \InvalidArgumentException('MultiLineStringZ must contain at least one LineStringZ.');
        }

        $this->lineStrings = $lineStrings;
        return $this;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $segments = array_map(
            fn(LineStringZ $ls) => '(' . implode(',', array_map(
                    fn(PointZ $p) => sprintf('%s %s %s', $p->getX(), $p->getY(), $p->getZ()),
                    $ls->getPoints()
                )) . ')',
            $this->lineStrings
        );

        return 'MULTILINESTRING Z(' . implode(',', $segments) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'MultiLineString',
            'coordinates' => array_map(
                fn(LineStringZ $ls) => array_map(
                    fn(PointZ $p) => [$p->getX(), $p->getY(), $p->getZ()],
                    $ls->getPoints()
                ),
                $this->lineStrings
            )
        ];
    }
}
