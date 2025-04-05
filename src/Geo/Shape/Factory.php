<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class Factory
{
    /**
     * @param string $geoJsonStr
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return Geometry
     */
    public static function createFromGeoJSONString(string $geoJsonStr, int|null $srid = null): Geometry
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (empty($geoJsonStr)) {
            throw new \InvalidArgumentException('GeoJSON string cannot be empty.');
        }

        $geoJsonData = json_decode($geoJsonStr, true);
        return self::createFromGeoJSONData($geoJsonData, $srid);
    }

    /**
     * @param array<mixed> $geoJsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return Geometry
     */
    public static function createFromGeoJSONData(array $geoJsonData, int|null $srid = null): Geometry
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (empty($geoJsonData)) {
            throw new \InvalidArgumentException('GeoJSON data cannot be empty.');
        }

        return match ($geoJsonData['type'] ?? null) {
            'Point' => Point::createFromGeoJSONData($geoJsonData, $srid),
            'MultiPoint' => MultiPoint::createFromGeoJSONData($geoJsonData, $srid),
            'LineString' => LineString::createFromGeoJSONData($geoJsonData, $srid),
            'MultiLineString' => MultiLineString::createFromGeoJSONData($geoJsonData, $srid),
            'Polygon' => Polygon::createFromGeoJSONData($geoJsonData, $srid),
            'MultiPolygon' => MultiPolygon::createFromGeoJSONData($geoJsonData, $srid),
            'GeometryCollection' => self::createFromGeoJSONData($geoJsonData, $srid), // recursive
            default => throw new \InvalidArgumentException("Unsupported geometry type in collection: ".$geoJsonData['type']),
        };
    }

    public static function createFromGeoEWKTString(string $ewktString): Geometry
    {
        if (str_contains($ewktString, 'CIRCULARSTRING')) {
            return CircularString::createFromGeoEWKTString($ewktString);
        } else {
            throw new \InvalidArgumentException('Unsupported geometry type in EWKT string: '.substr($ewktString, 0, 50).'...');
        }
    }
}