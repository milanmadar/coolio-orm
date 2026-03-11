<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

class PolygonZM extends AbstractShapeZM
{
    /** @var array<LineStringZM> */
    private array $lineStrings;

    /**
     * @param array<LineStringZM> $lineStrings
     * @param int|null $srid
     */
    public function __construct(array $lineStrings, int|null $srid = null)
    {
        $this->_validateRings($lineStrings);
        parent::__construct($srid);
        $this->lineStrings = $lineStrings;
    }

    /**
     * @return array<LineStringZM>
     */
    public function getLineStrings(): array
    {
        return $this->lineStrings;
    }

    /**
     * @param array<LineStringZM> $lineStrings
     * @return $this
     */
    public function setLineStrings(array $lineStrings): self
    {
        $this->_validateRings($lineStrings);
        $this->lineStrings = $lineStrings;
        return $this;
    }

    /**
     * Convert to WKT with ZM
     * @return string
     */
    public function toWKT(): string
    {
        $ringStrings = array_map(
            fn(LineStringZM $ls) => '(' . implode(',', array_map(
                    fn(PointZM $p) => sprintf('%s %s %s %s', $p->getX(), $p->getY(), $p->getZ(), $p->getM()),
                    $ls->getPoints()
                )) . ')',
            $this->lineStrings
        );

        return 'POLYGON ZM(' . implode(',', $ringStrings) . ')';
    }

    /**
     * Convert to GeoJSON (include 4th element M)
     * @return array<string, mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => array_map(
                fn(LineStringZM $ls) => array_map(
                    fn(PointZM $p) => $p->getCoordinates(),
                    $ls->getPoints()
                ),
                $this->lineStrings
            )
        ];
    }

    /**
     * @param array<string, mixed> $jsonData
     * @param int|null $srid
     * @return PolygonZM
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): PolygonZM
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (!isset($jsonData['type'], $jsonData['coordinates']) || $jsonData['type'] !== 'Polygon') {
            throw new \InvalidArgumentException('Invalid GeoJSON for PolygonZM.');
        }

        $lineStrings = [];
        foreach ($jsonData['coordinates'] as $ringCoords) {
            $points = array_map(
                fn($coords) => new PointZM((float)$coords[0], (float)$coords[1], (float)$coords[2], (float)$coords[3], $srid),
                $ringCoords
            );
            $lineStrings[] = new LineStringZM($points, $srid);
        }

        return new PolygonZM($lineStrings, $srid);
    }

    /**
     * @param string $ewktString
     * @return PolygonZM
     */
    public static function createFromGeoEWKTString(string $ewktString): PolygonZM
    {
        if (strpos($ewktString, ';POLYGON') === false) {
            throw new \InvalidArgumentException('Invalid EWKT string for PolygonZM.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) != 2) {
            throw new \InvalidArgumentException('Invalid EWKT string, missing SRID or geometry.');
        }

        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT.');
        }

        $srid = (int) substr($sridPart, 5);

        preg_match('/POLYGON ?Z?M?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid POLYGON ZM format in EWKT.');
        }

        $ringsData = explode('),', $matches[1]);
        $rings = [];

        foreach ($ringsData as $ringData) {
            $ringData = trim($ringData, " ()");
            $points = [];
            foreach (explode(',', $ringData) as $pointData) {
                $coords = array_map('trim', explode(' ', $pointData));
                if (count($coords) !== 4) {
                    throw new \InvalidArgumentException('Each point must have 4 coordinates for ZM.');
                }
                $points[] = new PointZM(
                    (float)$coords[0],
                    (float)$coords[1],
                    (float)$coords[2],
                    (float)$coords[3],
                    $srid
                );
            }
            $rings[] = new LineStringZM($points, $srid);
        }

        return new PolygonZM($rings, $srid);
    }

    /**
     * Validates rings: closed, minimum 4 points, winding order.
     * @param array<LineStringZM> $lineStrings
     * @throws \InvalidArgumentException
     */
    private function _validateRings(array $lineStrings): void
    {
        if (empty($lineStrings)) {
            throw new \InvalidArgumentException('PolygonZM must have at least one LineStringZM.');
        }

        foreach ($lineStrings as $lineString) {
            $points = $lineString->getPoints();
            if (count($points) < 4 || !$points[0]->equals(end($points))) {
                throw new \InvalidArgumentException('All rings must be closed with at least 4 points.');
            }
        }

        // Winding order: outer ring CCW, holes CW
        foreach ($lineStrings as $i => $ls) {
            $points = $ls->getPoints();
            $isOuter = ($i === 0);
            if ($isOuter && !$this->_isCCW($points)) {
                $ls->setPoints(array_reverse($points));
            } elseif (!$isOuter && $this->_isCCW($points)) {
                $ls->setPoints(array_reverse($points));
            }
        }
    }

    /**
     * Determines if points are counter-clockwise.
     * @param array<PointZM> $points
     * @return bool
     */
    private function _isCCW(array $points): bool
    {
        $sum = 0;
        $n = count($points);
        for ($i = 0; $i < $n - 1; $i++) {
            $sum += ($points[$i+1]->getX() - $points[$i]->getX()) *
                ($points[$i+1]->getY() + $points[$i]->getY());
        }
        return $sum < 0;
    }
}