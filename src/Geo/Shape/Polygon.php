<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class Polygon extends Geometry
{
    /**
     * @var LineString[] The first LineString is the outer ring, others are holes.
     */
    private array $lineStrings;

    /**
     * @param array $jsonData
     * @param int|null $srid
     * @return Polygon
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): static
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'Polygon' ||
            !is_array($jsonData['coordinates']) ||
            empty($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for Polygon.');
        }

        $lineStrings = [];

        foreach ($jsonData['coordinates'] as $lineCoords) {
            $points = array_map(fn($coords) => new Point((float)$coords[0], (float)$coords[1], $srid), $lineCoords);
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
            throw new \InvalidArgumentException('A Polygon must have at least one LineString.');
        }

        $this->_validateRings($lineStrings);

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
    public function setLineStrings(array $lineStrings): Polygon
    {
        $this->_validateRings($lineStrings);
        $this->lineStrings = $lineStrings;
        return $this;
    }

    public function toWKT(): string
    {
        $ringStrings = array_map(
            fn(LineString $ls) => '(' . implode(', ', array_map(
                    fn(Point $p) => sprintf('%F %F', $p->getX(), $p->getY()),
                    $ls->getPoints()
                )) . ')',
            $this->lineStrings
        );

        return 'POLYGON(' . implode(', ', $ringStrings) . ')';
    }

    public function toGeoJSON(): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => array_map(
                fn(LineString $ls) => array_map(
                    fn(Point $p) => [$p->getX(), $p->getY()],
                    $ls->getPoints()
                ),
                $this->lineStrings
            )
        ];
    }

    /**
     * Validates that the first and last points of the LineString are the same.
     * @param array<LineString> $lineStrings
     * @throws \InvalidArgumentException
     */
    private function _validateRings(array $lineStrings): void
    {
        if(empty($lineStrings)) {
            throw new \InvalidArgumentException('A Polygon must have at least one LineString.');
        }

        foreach ($lineStrings as $lineString) {
            $points = $lineString->getPoints();
            if (count($points) < 4 || $points[0] != end($points)) {
                throw new \InvalidArgumentException('All rings must be a closed LineString (minimum 4 points, first and last point must be the same).');
            }
        }
    }

}
