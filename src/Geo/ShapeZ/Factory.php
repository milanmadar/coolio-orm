<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

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
     * @return GeometryZ
     */
    public static function createFromGeoJSONData(array $geoJsonData, int|null $srid = null): GeometryZ
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (empty($geoJsonData)) {
            throw new \InvalidArgumentException('GeoJSON data cannot be empty.');
        }

        return match ($geoJsonData['type'] ?? null) {
            'Point' => PointZ::createFromGeoJSONData($geoJsonData, $srid),
            default => throw new \InvalidArgumentException("Unsupported geometry type in collection: ".$geoJsonData['type']),
        };
    }

    public static function createFromGeoEWKTString(string $ewktString): GeometryZ
    {
        return PointZ::createFromGeoEWKTString('nothing', 1);
    }
}