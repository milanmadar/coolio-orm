<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class MultiPolygonZ extends AbstractShapeZ
{
    /** @var array<PolygonZ> */
    private array $polygons;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return MultiPolygonZ
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): MultiPolygonZ
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'MultiPolygon' ||
            !is_array($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for MultiPolygonZ.');
        }

        $polygons = [];

        foreach ($jsonData['coordinates'] as $polygonCoords) {
            if (!is_array($polygonCoords) || empty($polygonCoords)) {
                throw new \InvalidArgumentException('Each MultiPolygon must contain valid coordinates.');
            }

            $rings = [];
            foreach ($polygonCoords as $ringCoords) {
                $points = array_map(fn($coords) => new PointZ((float)$coords[0], (float)$coords[1], (float)$coords[2], $srid), $ringCoords);
                $rings[] = new LineStringZ($points, $srid);
            }

            $polygons[] = new PolygonZ($rings, $srid);
        }

        return new MultiPolygonZ($polygons, $srid);
    }

    /**
     * @param string $ewktString
     * @return MultiPolygonZ
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiPolygonZ
    {
        // Parse the EWKT string, expected format: SRID=<srid>;MULTIPOLYGONZ(((<x1> <y1> <z1>, <x2> <y2> <z2>, ...), (<x3> <y3> <z3>, <x4> <y4> <z4>, ...)), ((...)))
        //if (strpos($ewktString, 'MULTIPOLYGONZ') === false) {
        if (strpos($ewktString, 'MULTIPOLYGON') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected MULTIPOLYGONZ type.');
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

        // Validate and extract the MULTIPOLYGONZ coordinates
        preg_match('/MULTIPOLYGON ?Z?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid MULTIPOLYGONZ format in EWKT.');
        }

        // Now we need to split the segments inside the CompoundCurve.
        // We will use a more careful approach to handle commas within parentheses.
        $geometryPart = $matches[1];
        $segments = [];
        $parenCount = 0;
        $currentSegment = '';

        // Iterate through the geometry part and properly extract the segments
        for ($i = 0; $i < strlen($geometryPart); $i++) {
            $char = $geometryPart[$i];
            if ($char === '(') {
                $parenCount++;
            } elseif ($char === ')') {
                $parenCount--;
            }

            // We only split the segments when the parentheses are balanced
            if ($parenCount === 0 && $char === ',') {
                // End of one segment, add it to the segments array
                $segments[] = trim($currentSegment);
                $currentSegment = '';
            } else {
                // Continue building the current segment
                $currentSegment .= $char;
            }
        }

        // Add the last segment
        if (!empty($currentSegment)) {
            $segments[] = trim($currentSegment);
        }

        $polygons = [];
        foreach ($segments as $polygonData)
        {
            $polygonData = trim($polygonData);

            if ($polygonData[0] === '(') {
                $polygonData = substr($polygonData, 1);
            }
            if (substr($polygonData, -1) === ')') {
                $polygonData = substr($polygonData, 0, -1);
            }

            $rings = [];
            $ringsData = explode('),', $polygonData);
            foreach ($ringsData as $ringData)
            {
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

            // Create a PolygonZ for each set of rings
            $polygons[] = new PolygonZ($rings, $srid);
        }

        return new MultiPolygonZ($polygons, $srid);
    }

    /**
     * @param array<PolygonZ> $polygons
     * @param int|null $srid
     */
    public function __construct(array $polygons, int|null $srid = null)
    {
        if (empty($polygons)) {
            throw new \InvalidArgumentException('MultiPolygonZ must contain at least one PolygonZ.');
        }

        parent::__construct($srid);
        $this->polygons = $polygons;
    }

    /**
     * @return array<PolygonZ>
     */
    public function getPolygons(): array
    {
        return $this->polygons;
    }

    /**
     * @param array<PolygonZ> $polygons
     * @return $this
     */
    public function setPolygons(array $polygons): MultiPolygonZ
    {
        if (empty($polygons)) {
            throw new \InvalidArgumentException('MultiPolygonZ must contain at least one PolygonZ.');
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
            fn(PolygonZ $p) => '(' . implode(',', array_map(
                    fn(LineStringZ $ls) => '(' . implode(',', array_map(
                            fn(PointZ $p) => sprintf('%s %s %s', $p->getX(), $p->getY(), $p->getZ()),
                            $ls->getPoints()
                        )) . ')',
                    $p->getLineStrings()
                )) . ')',
            $this->polygons
        );

        return 'MULTIPOLYGONZ(' . implode(',', $polygonStrings) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'MultiPolygon',
            'coordinates' => array_map(
                fn(PolygonZ $p) => array_map(
                    fn(LineStringZ $ls) => array_map(
                        fn(PointZ $p) => [$p->getX(), $p->getY(), $p->getZ()],
                        $ls->getPoints()
                    ),
                    $p->getLineStrings()
                ),
                $this->polygons
            )
        ];
    }
}
