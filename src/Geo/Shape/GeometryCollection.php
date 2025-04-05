<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class GeometryCollection extends Geometry
{
    /** @var array<Geometry> */
    private array $geometries;

    /**
     * @param array<mixed> $jsonData
     * @return GeometryCollection
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): static
    {
        if (
            !isset($jsonData['type'], $jsonData['geometries']) ||
            $jsonData['type'] !== 'GeometryCollection' ||
            !is_array($jsonData['geometries'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for GeometryCollection.');
        }

        $geometries = [];

        foreach ($jsonData['geometries'] as $geometryData) {
            $geometries[] = Factory::createFromGeoJSONData($geometryData, $srid);
        }

        return new static($geometries, $srid);
    }

    /**
     * @param array<Geometry> $geometries
     * @param int|null $srid
     */
    public function __construct(array $geometries, int|null $srid = null)
    {
        parent::__construct($srid);
        $this->geometries = $geometries;
    }

    public function toWKT(): string
    {
        $wktGeometries = array_map(fn(Geometry $g) => $g->toWKT(), $this->geometries);
        return 'GEOMETRYCOLLECTION(' . implode(', ', $wktGeometries) . ')';
    }

    public function toGeoJSON(): array
    {
        return [
            'type' => 'GeometryCollection',
            'geometries' => array_map(fn(Geometry $g) => $g->toGeoJSON(), $this->geometries)
        ];
    }

    /**
     * @return Geometry[]
     */
    public function getGeometries(): array
    {
        return $this->geometries;
    }
}
