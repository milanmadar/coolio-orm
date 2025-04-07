<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class PolygonZ extends GeometryZ
{
    /* @var LineStringZ[] */
    private array $lineStrings;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return PolygonZ
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): PolygonZ
    {
        if (!isset($srid)) {
            $srid = $_ENV['GEO_DEFAULT_SRID'];
        }

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'Polygon' ||
            !is_array($jsonData['coordinates']) ||
            empty($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for PolygonZ.');
        }

        $lineStrings = [];

        foreach ($jsonData['coordinates'] as $lineCoords) {
            $points = array_map(
                fn($coords) => new PointZ((float)$coords[0], (float)$coords[1], (float)$coords[2], $srid),
                $lineCoords
            );
            $lineStrings[] = new LineStringZ($points, $srid);
        }

        return new PolygonZ($lineStrings, $srid);
    }

    /**
     * @param string $ewktString
     * @return PolygonZ
     */
    public static function createFromGeoEWKTString(string $ewktString): PolygonZ
    {
        // Parse the EWKT string, expected format: SRID=<srid>;POLYGON Z((<x1> <y1> <z1>, <x2> <y2> <z2>, ...), (<x3> <y3> <z3>, <x4> <y4> <z4>, ...))
        //if (strpos($ewktString, 'POLYGON Z') === false) {
        if (strpos($ewktString, ';POLYGON') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected POLYGON Z type.');
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

        // Validate and extract the POLYGON Z coordinates
        preg_match('/POLYGON ?Z?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid POLYGON Z format in EWKT.');
        }

        $ringsData = explode('),', $matches[1]);
        $rings = [];

        foreach ($ringsData as $ringData) {
            // Clean up the ring (remove extra spaces and commas)
            $ringData = trim($ringData);
            if ($ringData[0] === '(') {
                $ringData = substr($ringData, 1);
            }
            if (substr($ringData, -1) === ')') {
                $ringData = substr($ringData, 0, -1);
            }

            $pointsData = explode(',', $ringData);
            $points = [];

            foreach ($pointsData as $pointData) {
                $coords = array_map('trim', explode(' ', $pointData));
                if (count($coords) !== 3) {
                    throw new \InvalidArgumentException('Each point in the ring must have exactly 3 coordinates.');
                }

                $points[] = new PointZ((float) $coords[0], (float) $coords[1], (float) $coords[2], $srid);
            }

            // Create a LineStringZ for each ring (it may have multiple points)
            $rings[] = new LineStringZ($points, $srid);
        }

        return new PolygonZ($rings, $srid);
    }

    /**
     * @param array<LineStringZ> $lineStrings
     * @param int|null $srid
     */
    public function __construct(array $lineStrings, int|null $srid = null)
    {
        $this->_validateRings($lineStrings);
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
    public function setLineStrings(array $lineStrings): PolygonZ
    {
        $this->_validateRings($lineStrings);
        $this->lineStrings = $lineStrings;
        return $this;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $ringStrings = array_map(
            fn(LineStringZ $ls) => '(' . implode(',', array_map(
                    fn(PointZ $p) => sprintf('%s %s %s', $p->getX(), $p->getY(), $p->getZ()),
                    $ls->getPoints()
                )) . ')',
            $this->lineStrings
        );

        return 'POLYGON Z(' . implode(',', $ringStrings) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => array_map(
                fn(LineStringZ $ls) => array_map(
                    fn(PointZ $p) => [$p->getX(), $p->getY(), $p->getZ()],
                    $ls->getPoints()
                ),
                $this->lineStrings
            )
        ];
    }

    /**
     * Validates that the first and last points of the LineString are the same.
     * @param array<LineStringZ> $lineStrings
     * @throws \InvalidArgumentException
     */
    private function _validateRings(array $lineStrings): void
    {
        if (empty($lineStrings)) {
            throw new \InvalidArgumentException('A PolygonZ must have at least one LineString.');
        }

        foreach ($lineStrings as $lineString) {
            $points = $lineString->getPoints();
            if (count($points) < 4 || $points[0] != end($points)) {
                throw new \InvalidArgumentException('All rings must be a closed LineStringZ (minimum 4 points, first and last point must be the same).');
            }
        }
    }
}
