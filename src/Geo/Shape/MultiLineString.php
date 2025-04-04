<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class MultiLineString extends Geometry
{
    /** @var array<LineString> */
    private array $lineStrings;

    /**
     * @param array $jsonData
     * @param int|null $srid
     * @return MultiLineString
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): static
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'MultiLineString' ||
            !is_array($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for MultiLineString');
        }

        $lineStrings = [];

        foreach ($jsonData['coordinates'] as $lineCoords) {
            if (!is_array($lineCoords) || count($lineCoords) < 2) {
                throw new \InvalidArgumentException('Each LineString must have at least 2 coordinates.');
            }

            $points = [];

            foreach ($lineCoords as $coords) {
                if (!is_array($coords) || count($coords) !== 2) {
                    throw new \InvalidArgumentException('Invalid coordinate in MultiLineString');
                }
                $points[] = new Point((float)$coords[0], (float)$coords[1], $srid);
            }

            $lineStrings[] = new LineString($points, $srid);
        }

        return new static($lineStrings, $srid);
    }

    /**
     * @param array<LineString> $lineStrings
     * @param int|null $srid
     */
    public function __construct(array $lineStrings, int|null $srid = null)
    {
        if (empty($lineStrings)) {
            throw new \InvalidArgumentException('MultiLineString must contain at least one LineString.');
        }

        parent::__construct($srid);
        $this->lineStrings = $lineStrings;
    }

    /**
     * @return array<LineString>
     */
    public function getLineStrings(): array
    {
        return $this->lineStrings;
    }

    /**
     * @param array<LineString> $lineStrings
     * @return $this
     */
    public function setLineStrings(array $lineStrings): MultiLineString
    {
        if (empty($lineStrings)) {
            throw new \InvalidArgumentException('MultiLineString must contain at least one LineString.');
        }

        $this->lineStrings = $lineStrings;
        return $this;
    }

    public function toWKT(): string
    {
        $segments = array_map(
            fn(LineString $ls) => '(' . implode(', ', array_map(
                    fn(Point $p) => sprintf('%F %F', $p->getX(), $p->getY()),
                    $ls->getPoints()
                )) . ')',
            $this->lineStrings
        );

        return 'MULTILINESTRING(' . implode(', ', $segments) . ')';
    }

    public function toGeoJSON(): array
    {
        return [
            'type' => 'MultiLineString',
            'coordinates' => array_map(
                fn(LineString $ls) => array_map(
                    fn(Point $p) => [$p->getX(), $p->getY()],
                    $ls->getPoints()
                ),
                $this->lineStrings
            )
        ];
    }
}
