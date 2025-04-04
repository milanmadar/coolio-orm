<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class Factory
{
    public static function createFromGeoJSONString(string $geoJsonStr, int|null $srid = null): Geometry
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (empty($geoJsonStr)) {
            throw new \InvalidArgumentException('GeoJSON string cannot be empty.');
        }

        $geoJsonData = json_decode($geoJsonStr, true);
        return self::createFromGeoJSONData($geoJsonData, $srid);
    }

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
}