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
        if(empty($lineStrings)) {
            throw new \InvalidArgumentException('PolygonZM must have at least one LineStringZM.');
        }
        if(!isset($srid)) $srid = $lineStrings[0]->getSrid();
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
                    fn(PointZM $p) => sprintf('%.8f %.8f %.8f %.8f', $p->getX(), $p->getY(), $p->getZ(), $p->getM()),
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
        $ewktString = str_replace('; ', ';', $ewktString);
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
                $coords = array_map('trim', explode(' ', trim($pointData)));
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
     * Calculates the geometric centroid (center of mass) of the Polygon.
     * For simplicity and standard 2D representation, this uses the exterior ring.
     * @return PointZM
     */
    public function getCenterPoint(): PointZM
    {
        $exteriorRing = $this->getLineStrings()[0]->getPoints();
        $n = count($exteriorRing);

        // take the M from the middle
        $middleM = $exteriorRing[(int)($n/2)]->getM();

        $area = 0.0;
        $cx = 0.0;
        $cy = 0.0;
        $czSum = 0.0;

        for ($i = 0; $i < $n - 1; $i++) {
            $p1 = $exteriorRing[$i];
            $p2 = $exteriorRing[$i + 1];

            $x1 = $p1->getX(); $y1 = $p1->getY();
            $x2 = $p2->getX(); $y2 = $p2->getY();

            // 2D Centroid Math (Shoelace)
            $crossProduct = ($x1 * $y2) - ($x2 * $y1);
            $area += $crossProduct;
            $cx += ($x1 + $x2) * $crossProduct;
            $cy += ($y1 + $y2) * $crossProduct;

            // 3D Elevation Math (Arithmetic Mean of Vertices)
            // We sum the Z of the current point (excluding the closing point later)
            $czSum += $p1->getZ();
        }

        $area *= 0.5;

        // Fallback for zero-area (e.g., vertical polygons or lines)
        if (abs($area) < 1e-9) {
            $minX = $minY = $minZ = PHP_FLOAT_MAX;
            $maxX = $maxY = $maxZ = -PHP_FLOAT_MAX;
            foreach ($exteriorRing as $p) {
                $minX = min($minX, $p->getX()); $maxX = max($maxX, $p->getX());
                $minY = min($minY, $p->getY()); $maxY = max($maxY, $p->getY());
                $minZ = min($minZ, $p->getZ()); $maxZ = max($maxZ, $p->getZ());
            }
            return new PointZM(
                ($minX + $maxX) / 2,
                ($minY + $maxY) / 2,
                ($minZ + $maxZ) / 2,
                $middleM
            );
        }

        $finalX = $cx / (6.0 * $area);
        $finalY = $cy / (6.0 * $area);
        $finalZ = $czSum / ($n - 1); // Average elevation of unique vertices

        return new PointZM($finalX, $finalY, $finalZ, $middleM);
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
            if (count($points) < 4 || !$points[0]->equalsXYZ(end($points))) {
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

    public function __clone(): void
    {
        $clones = [];
        foreach ($this->lineStrings as $geom) {
            $clones[] = clone $geom;
        }
        $this->lineStrings = $clones;
    }
}