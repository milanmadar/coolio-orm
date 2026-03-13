<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class MultiPolygonZ extends AbstractShapeZ
{
    /** @var array<PolygonZ> */
    private array $polygons;

    /**
     * @param array<string, mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return MultiPolygonZ
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): MultiPolygonZ
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
        $geometryPart = trim((string)preg_replace('/^MULTIPOLYGON ?Z?\s*/i', '', $geometryPart));

        // remove spaces
        $geometryPart = str_replace(['   ','  ','( ', ' )',', ', ' ,'], [' ',' ','(', ')',',', ','], $geometryPart);

        // remove the first and last 1 parentheses
        $geometryPart = substr($geometryPart, 1, -1);

        $polygons = [];
        if(str_contains($geometryPart, ')),((')) {
            $parts = explode(')),((', $geometryPart);
            foreach ($parts as $part) {
                $polygonString = '((' . trim($part, ' ()') . '))';
                $polygons[] = PolygonZ::createFromGeoEWKTString("SRID=$srid;POLYGON Z$polygonString");
            }
        } else {
            $polygons[] = PolygonZ::createFromGeoEWKTString("SRID=$srid;POLYGON Z$geometryPart");
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

        if(!isset($srid)) $srid = $polygons[0]->getSrid();
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

        return 'MULTIPOLYGON Z(' . implode(',', $polygonStrings) . ')';
    }

    /**
     * @return array<string, mixed>
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
