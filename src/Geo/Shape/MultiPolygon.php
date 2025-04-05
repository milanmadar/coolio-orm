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
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): static
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

        return new static($polygons, $srid);
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

    public function toWKT(): string
    {
        $polygonStrings = array_map(
            fn(Polygon $p) => '(' . implode(', ', array_map(
                    fn(LineString $ls) => '(' . implode(', ', array_map(
                            fn(Point $p) => sprintf('%F %F', $p->getX(), $p->getY()),
                            $ls->getPoints()
                        )) . ')',
                    $p->getRings()
                )) . ')',
            $this->polygons
        );

        return 'MULTIPOLYGON(' . implode(', ', $polygonStrings) . ')';
    }

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
                    $p->getRings()
                ),
                $this->polygons
            )
        ];
    }
}
