<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

class MultiPolygonZM extends AbstractShapeZM
{
    /** @var array<PolygonZM> */
    private array $polygons;

    /**
     * @param array<PolygonZM> $polygons
     * @param int|null $srid
     */
    public function __construct(array $polygons, int|null $srid = null)
    {
        if (empty($polygons)) {
            throw new \InvalidArgumentException('MultiPolygonZM must contain at least one PolygonZM.');
        }

        parent::__construct($srid);
        $this->polygons = $polygons;
    }

    /**
     * @return array<PolygonZM>
     */
    public function getPolygons(): array
    {
        return $this->polygons;
    }

    /**
     * @param array<PolygonZM> $polygons
     * @return $this
     */
    public function setPolygons(array $polygons): self
    {
        if (empty($polygons)) {
            throw new \InvalidArgumentException('MultiPolygonZM must contain at least one PolygonZM.');
        }
        $this->polygons = $polygons;
        return $this;
    }

    /**
     * @param array<string, mixed> $jsonData
     * @param int|null $srid
     * @return MultiPolygonZM
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): MultiPolygonZM
    {
        if (!isset($jsonData['type'], $jsonData['coordinates']) || $jsonData['type'] !== 'MultiPolygon') {
            throw new \InvalidArgumentException('Invalid GeoJSON for MultiPolygonZM.');
        }

        $polygons = [];
        foreach ($jsonData['coordinates'] as $polyCoords) {
            $polygonData = ['type' => 'Polygon', 'coordinates' => $polyCoords];
            $polygons[] = PolygonZM::createFromGeoJSON($polygonData, $srid);
        }

        return new MultiPolygonZM($polygons);
    }

    public static function createFromGeoEWKTString(string $ewktString): MultiPolygonZM
    {
        if (strpos($ewktString, ';MULTIPOLYGON') === false) {
            throw new \InvalidArgumentException('Invalid EWKT string for MultiPolygonZM.');
        }

        // Extract SRID and geometry
        [$sridPart, $geometryPart] = explode(';', $ewktString, 2);
        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT.');
        }
        $srid = (int) substr($sridPart, 5);

        // Remove leading "MULTIPOLYGON ZM" and trim
        $geometryPart = trim((string)preg_replace('/^MULTIPOLYGON ?Z?M?\s*/i', '', $geometryPart));

        // remove spaces
        $geometryPart = str_replace(['   ','  ','( ', ' )',', ', ' ,'], [' ',' ','(', ')',',', ','], $geometryPart);

        // remove the first and last 1 parentheses
        $geometryPart = substr($geometryPart, 1, -1);

        $polygons = [];
        if(str_contains($geometryPart, ')),((')) {
            $parts = explode(')),((', $geometryPart);
            foreach ($parts as $part) {
                $polygonString = '((' . trim($part, ' ()') . '))';
                $polygons[] = PolygonZM::createFromGeoEWKTString("SRID=$srid;POLYGON ZM$polygonString");
            }
        } else {
            $polygons[] = PolygonZM::createFromGeoEWKTString("SRID=$srid;POLYGON ZM$geometryPart");
        }

        return new MultiPolygonZM($polygons, $srid);
    }

    /**
     * @param string $ewktString
     * @return MultiPolygonZM
     */
    public static function BAKcreateFromGeoEWKTString(string $ewktString): MultiPolygonZM
    {
        if (strpos($ewktString, ';MULTIPOLYGON') === false) {
            throw new \InvalidArgumentException('Invalid EWKT string for MultiPolygonZM.');
        }

        // Extract SRID and geometry
        [$sridPart, $geometryPart] = explode(';', $ewktString, 2);
        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT.');
        }
        $srid = (int) substr($sridPart, 5);

        // Remove leading "MULTIPOLYGON ZM" and trim
        $geometryPart = trim((string)preg_replace('/^MULTIPOLYGON ?Z?M?\s*/i', '', $geometryPart));

        // Now parse polygons with a parentheses-aware parser
        $polygons = [];
        $depth = 0;
        $buffer = '';
        $ringsStrings = [];

        $chars = str_split($geometryPart);
        foreach ($chars as $c) {
            if ($c === '(') {
                $depth++;
                if ($depth >= 2) $buffer .= $c; // only start recording inside double parentheses
            } elseif ($c === ')') {
                if ($depth >= 2) $buffer .= $c;
                $depth--;
                if ($depth === 1) { // finished a polygon
                    $ringsStrings[] = $buffer;
                    $buffer = '';
                }
            } elseif ($depth >= 2) {
                $buffer .= $c;
            }
        }

        foreach ($ringsStrings as $polygonString) {
            $polygonString = trim($polygonString);
            $ringStrings = [];
            $depthRing = 0;
            $bufferRing = '';
            $charsRing = str_split($polygonString);
            foreach ($charsRing as $c) {
                if ($c === '(') {
                    $depthRing++;
                    if ($depthRing >= 1) $bufferRing .= $c;
                } elseif ($c === ')') {
                    if ($depthRing >= 1) $bufferRing .= $c;
                    $depthRing--;
                    if ($depthRing === 0) {
                        $ringStrings[] = $bufferRing;
                        $bufferRing = '';
                    }
                } elseif ($depthRing >= 1) {
                    $bufferRing .= $c;
                }
            }

            $rings = [];
            foreach ($ringStrings as $ringString) {
                $ringString = trim($ringString, " ()");
                $points = [];
                foreach (explode(',', $ringString) as $pointData) {
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

            $polygons[] = new PolygonZM($rings, $srid);
        }

        return new MultiPolygonZM($polygons, $srid);
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $polyStrings = [];
        foreach ($this->polygons as $polygon) {
            $polyStrings[] = '(' . implode(',', array_map(
                    fn(LineStringZM $ls) => '(' . implode(',', array_map(
                            fn(PointZM $p) => sprintf('%s %s %s %s', $p->getX(), $p->getY(), $p->getZ(), $p->getM()),
                            $ls->getPoints()
                        )) . ')',
                    $polygon->getLineStrings()
                )) . ')';
        }

        return 'MULTIPOLYGON ZM(' . implode(',', $polyStrings) . ')';
    }

    /**
     * @return array<string, mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'MultiPolygon',
            'coordinates' => array_map(
                fn(PolygonZM $polygon) => array_map(
                    fn(LineStringZM $ls) => array_map(
                        fn(PointZM $p) => [$p->getX(), $p->getY(), $p->getZ(), $p->getM()],
                        $ls->getPoints()
                    ),
                    $polygon->getLineStrings()
                ),
                $this->polygons
            )
        ];
    }
}