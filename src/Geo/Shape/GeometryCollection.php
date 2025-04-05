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
     * @param string $ewktString
     * @return GeometryCollection
     */
    public static function createFromGeoEWKTString(string $ewktString): static
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
            // Clean up each geometry string (remove extra spaces)
            $geometry = trim($geometry);

            // Check the type of the geometry and delegate to the appropriate create method
            if (str_starts_with($geometry, 'POINT')) {
                $geometries[] = Point::createFromGeoEWKTString("SRID=$srid;$geometry");
            } elseif (str_starts_with($geometry, 'LINESTRING')) {
                $geometries[] = LineString::createFromGeoEWKTString("SRID=$srid;$geometry");
            } elseif (str_starts_with($geometry, 'POLYGON')) {
                $geometries[] = Polygon::createFromGeoEWKTString("SRID=$srid;$geometry");
            } elseif (str_starts_with($geometry, 'MULTIPOINT')) {
                $geometries[] = MultiPoint::createFromGeoEWKTString("SRID=$srid;$geometry");
            } elseif (str_starts_with($geometry, 'MULTILINESTRING')) {
                $geometries[] = MultiLineString::createFromGeoEWKTString("SRID=$srid;$geometry");
            } elseif (str_starts_with($geometry, 'MULTIPOLYGON')) {
                $geometries[] = MultiPolygon::createFromGeoEWKTString("SRID=$srid;$geometry");
            } else {
                // Handle other geometries or throw an error if unknown type
                throw new \InvalidArgumentException("Unsupported geometry type in GeomtryCollection: $geometry");
            }
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
