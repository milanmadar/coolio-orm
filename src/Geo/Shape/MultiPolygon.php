<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class MultiPolygon extends Geometry
{
    /** @var array<Polygon> */
    private array $polygons;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return MultiPolygon
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): MultiPolygon
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'MultiPolygon' ||
            !is_array($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for MultiPolygon.');
        }

        $polygons = [];

        foreach ($jsonData['coordinates'] as $polygonCoords) {
            if (!is_array($polygonCoords) || empty($polygonCoords)) {
                throw new \InvalidArgumentException('Each MultiPolygon must contain valid coordinates.');
            }

            $rings = [];
            foreach ($polygonCoords as $ringCoords) {
                $points = array_map(fn($coords) => new Point((float)$coords[0], (float)$coords[1], $srid), $ringCoords);
                $rings[] = new LineString($points, $srid);
            }

            $polygons[] = new Polygon($rings, $srid);
        }

        return new MultiPolygon($polygons, $srid);
    }

    /**
     * @param string $ewktString
     * @return MultiPolygon
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiPolygon
    {
        // Parse the EWKT string, expected format: SRID=<srid>;MULTIPOLYGON(((<x1> <y1>, <x2> <y2>, ...), (<x3> <y3>, <x4> <y4>, ...)), ((...)))
        if (strpos($ewktString, 'MULTIPOLYGON') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected MULTIPOLYGON type.');
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

        // Validate and extract the MULTIPOLYGON coordinates
        preg_match('/MULTIPOLYGON\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid MULTIPOLYGON format in EWKT.');
        }

        $polygonsData = explode('),(', $matches[1]);
        $polygons = [];

        foreach ($polygonsData as $polygonData) {
            // Clean up the polygon (remove extra spaces and commas)
            $polygonData = trim($polygonData);
            if ($polygonData[0] === '(') {
                $polygonData = substr($polygonData, 1);
            }
            if (substr($polygonData, -1) === ')') {
                $polygonData = substr($polygonData, 0, -1);
            }

            $ringsData = explode('),', $polygonData);
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
                    if (count($coords) !== 2) {
                        throw new \InvalidArgumentException('Each point in the ring must have exactly 2 coordinates.');
                    }

                    $points[] = new Point((float) $coords[0], (float) $coords[1], $srid);
                }

                // Create a LineString for each ring (it may have multiple points)
                $rings[] = new LineString($points, $srid);
            }

            // Create a Polygon for each set of rings
            $polygons[] = new Polygon($rings, $srid);
        }

        return new MultiPolygon($polygons, $srid);
    }

    /**
     * @param array<Polygon> $polygons
     * @param int|null $srid
     */
    public function __construct(array $polygons, int|null $srid = null)
    {
        if (empty($polygons)) {
            throw new \InvalidArgumentException('MultiPolygon must contain at least one Polygon.');
        }

        parent::__construct($srid);
        $this->polygons = $polygons;
    }

    /**
     * @return array<Polygon>
     */
    public function getPolygons(): array
    {
        return $this->polygons;
    }

    /**
     * @param array<Polygon> $polygons
     * @return $this
     */
    public function setPolygons(array $polygons): MultiPolygon
    {
        if (empty($polygons)) {
            throw new \InvalidArgumentException('MultiPolygon must contain at least one Polygon.');
        }

        $this->polygons = $polygons;
        return $this;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $polygonStrings = array_map(
            fn(Polygon $p) => '(' . implode(',', array_map(
                    fn(LineString $ls) => '(' . implode(',', array_map(
                            fn(Point $p) => sprintf('%s %s', $p->getX(), $p->getY()),
                            $ls->getPoints()
                        )) . ')',
                    $p->getLineStrings()
                )) . ')',
            $this->polygons
        );

        return 'MULTIPOLYGON(' . implode(',', $polygonStrings) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'MultiPolygon',
            'coordinates' => array_map(
                fn(Polygon $p) => array_map(
                    fn(LineString $ls) => array_map(
                        fn(Point $p) => [$p->getX(), $p->getY()],
                        $ls->getPoints()
                    ),
                    $p->getLineStrings()
                ),
                $this->polygons
            )
        ];
    }
}
