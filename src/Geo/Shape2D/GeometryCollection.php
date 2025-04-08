<?php

namespace Milanmadar\CoolioORM\Geo\Shape2D;

use Milanmadar\CoolioORM\Geo\Shape2D3DFactory;

class GeometryCollection extends AbstractShape2D
{
    /** @var array<AbstractShape2D> */
    private array $geometries;

    /**
     * @param array<mixed> $jsonData
     * @return GeometryCollection
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): GeometryCollection
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
            /** @var AbstractShape2D $_ */
            $_ = Shape2D3DFactory::createFromGeoJSONData($geometryData, $srid);
            $geometries[] = $_;
        }

        return new GeometryCollection($geometries, $srid);
    }

    /**
     * @param string $ewktString
     * @return GeometryCollection
     */
    public static function createFromGeoEWKTString(string $ewktString): GeometryCollection
    {
        // Parse the EWKT string, expected format: SRID=<srid>;GEOMETRYCOLLECTION(<geometry1>, <geometry2>, <geometry3>, ...)
        if (strpos($ewktString, 'GEOMETRYCOLLECTION') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected GEOMETRYCOLLECTION type.');
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

        // Validate and extract the GEOMETRYCOLLECTION coordinates
        preg_match('/GEOMETRYCOLLECTION\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid GEOMETRYCOLLECTION format in EWKT.');
        }

        // Get the geometries within the collection
        $geometryData = explode(',', $matches[1]);
        $geometries = [];

        foreach ($geometryData as $geometry) {
            $geometry = trim($geometry);
            /** @var AbstractShape2D $_ */
            $_ = Shape2D3DFactory::createFromGeoEWKTString("SRID=$srid;$geometry");
            $geometries[] = $_;
        }

        return new GeometryCollection($geometries, $srid);
    }

    /**
     * @param array<AbstractShape2D> $geometries
     * @param int|null $srid
     */
    public function __construct(array $geometries, int|null $srid = null)
    {
        parent::__construct($srid);
        $this->geometries = $geometries;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $wktGeometries = array_map(fn(AbstractShape2D $g) => $g->toWKT(), $this->geometries);
        return 'GEOMETRYCOLLECTION(' . implode(',', $wktGeometries) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'GeometryCollection',
            'geometries' => array_map(fn(AbstractShape2D $g) => $g->toGeoJSON(), $this->geometries)
        ];
    }

    /**
     * @return AbstractShape2D[]
     */
    public function getGeometries(): array
    {
        return $this->geometries;
    }
}
