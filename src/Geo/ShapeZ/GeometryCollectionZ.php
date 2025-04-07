<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class GeometryCollectionZ extends GeometryZ
{
    /** @var array<GeometryZ> */
    private array $geometries;

    /**
     * @param array<mixed> $jsonData
     * @return GeometryCollectionZ
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): GeometryCollectionZ
    {
        if (
            !isset($jsonData['type'], $jsonData['geometries']) ||
            $jsonData['type'] !== 'GeometryCollection' ||
            !is_array($jsonData['geometries'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for GeometryCollectionZ.');
        }

        $geometries = [];

        foreach ($jsonData['geometries'] as $geometryData) {
            $geometries[] = Factory::createFromGeoJSONData($geometryData, $srid);
        }

        return new GeometryCollectionZ($geometries, $srid);
    }

    /**
     * @param string $ewktString
     * @return GeometryCollectionZ
     */
    public static function createFromGeoEWKTString(string $ewktString): GeometryCollectionZ
    {
        // Parse the EWKT string, expected format: SRID=<srid>;GEOMETRYCOLLECTIONZ(<geometry1>, <geometry2>, <geometry3>, ...)
        if (strpos($ewktString, 'GEOMETRYCOLLECTIONZ') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected GEOMETRYCOLLECTIONZ type.');
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

        // Validate and extract the GEOMETRYCOLLECTIONZ coordinates
        preg_match('/GEOMETRYCOLLECTIONZ\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid GEOMETRYCOLLECTIONZ format in EWKT.');
        }

        // Get the geometries within the collection
        $geometryData = explode(',', $matches[1]);
        $geometries = [];

        foreach ($geometryData as $geometry) {
            // Clean up each geometry string (remove extra spaces)
            $geometry = trim($geometry);

            // Check the type of the geometry and delegate to the appropriate create method
            if (str_starts_with($geometry, 'POINT')) {
                $geometries[] = PointZ::createFromGeoEWKTString("SRID=$srid;$geometry");
            } elseif (str_starts_with($geometry, 'LINESTRING')) {
                $geometries[] = LineStringZ::createFromGeoEWKTString("SRID=$srid;$geometry");
            } elseif (str_starts_with($geometry, 'POLYGON')) {
                $geometries[] = PolygonZ::createFromGeoEWKTString("SRID=$srid;$geometry");
            } elseif (str_starts_with($geometry, 'MULTIPOINT')) {
                $geometries[] = MultiPointZ::createFromGeoEWKTString("SRID=$srid;$geometry");
            } elseif (str_starts_with($geometry, 'MULTILINESTRING')) {
                $geometries[] = MultiLineStringZ::createFromGeoEWKTString("SRID=$srid;$geometry");
            } elseif (str_starts_with($geometry, 'MULTIPOLYGON')) {
                $geometries[] = MultiPolygonZ::createFromGeoEWKTString("SRID=$srid;$geometry");
            } else {
                // Handle other geometries or throw an error if unknown type
                throw new \InvalidArgumentException("Unsupported geometry type in GeometryCollectionZ: $geometry");
            }
        }

        return new GeometryCollectionZ($geometries, $srid);
    }

    /**
     * @param array<GeometryZ> $geometries
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
        $wktGeometries = array_map(fn(GeometryZ $g) => $g->toWKT(), $this->geometries);
        return 'GEOMETRYCOLLECTIONZ(' . implode(',', $wktGeometries) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'GeometryCollection',
            'geometries' => array_map(fn(GeometryZ $g) => $g->toGeoJSON(), $this->geometries)
        ];
    }

    /**
     * @return GeometryZ[]
     */
    public function getGeometries(): array
    {
        return $this->geometries;
    }
}
