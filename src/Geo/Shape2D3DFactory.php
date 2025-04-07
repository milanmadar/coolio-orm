<?php

namespace Milanmadar\CoolioORM\Geo;

class Shape2D3DFactory
{
    /**
     * Creates a Geometry object from a GeoJSON string.
     *
     * @param string $geoJsonStr The GeoJSON string to parse.
     * @param int|null $srid Optional SRID to assign to the geometry.
     * @return Shape\Geometry|ShapeZ\GeometryZ The created geometry (Point, PointZ, LineString, LineStringZ, etc.).
     * @throws \InvalidArgumentException If the GeoJSON is invalid or unsupported.
     */
    public static function createFromGeoJSONString(string $geoJsonStr, int|null $srid = null): Shape\Geometry|ShapeZ\GeometryZ
    {
        $geoJsonData = json_decode($geoJsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid GeoJSON string.');
        }

        // Check the type of geometry
        if (!isset($geoJsonData['type'])) {
            throw new \InvalidArgumentException('GeoJSON must contain "type".');
        }

        switch ($geoJsonData['type']) {
            case 'Point':
                $coordCount = count($geoJsonData['coordinates']);
                if ($coordCount == 2) {
                    return Shape\Point::createFromGeoJSONData($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\PointZ::createFromGeoJSONData($geoJsonData, $srid);
                }
                break;

            case 'LineString':
                $coordCount = count($geoJsonData['coordinates'][0]);
                if ($coordCount == 2) {
                    return Shape\LineString::createFromGeoJSONData($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\LineStringZ::createFromGeoJSONData($geoJsonData, $srid);
                }
                break;

            case 'Polygon':
                $coordCount = count($geoJsonData['coordinates'][0][0]);
                if ($coordCount == 2) {
                    return Shape\Polygon::createFromGeoJSONData($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\PolygonZ::createFromGeoJSONData($geoJsonData, $srid);
                }
                break;

            case 'MultiPoint':
                $coordCount = count($geoJsonData['coordinates'][0]);
                if ($coordCount == 2) {
                    return Shape\MultiPoint::createFromGeoJSONData($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\MultiPointZ::createFromGeoJSONData($geoJsonData, $srid);
                }
                break;

            case 'MultiLineString':
                return Shape\MultiLineString::createFromGeoJSONData($geoJsonData, $srid);
//                $coordCount = count($geoJsonData['coordinates'][0][0]);
//                if ($coordCount == 2) {
//                    return Shape\MultiLineString::createFromGeoJSONData($geoJsonData, $srid);
//                } elseif ($coordCount == 3) {
//                    return ShapeZ\MultiLineStringZ::createFromGeoJSONData($geoJsonData, $srid);
//                }
                break;

            case 'MultiPolygon':
                return Shape\MultiPolygon::createFromGeoJSONData($geoJsonData, $srid);
//                $coordCount = count($geoJsonData['coordinates'][0][0][0]);
//                if ($coordCount == 2) {
//                    return Shape\MultiPolygon::createFromGeoJSONData($geoJsonData, $srid);
//                } elseif ($coordCount == 3) {
//                    return ShapeZ\MultiPolygonZ::createFromGeoJSONData($geoJsonData, $srid);
//                }
                break;

            case 'GeometryCollection':
                $geometries = [];
                foreach ($geoJsonData['geometries'] as $geometry) {
                    $geometries[] = self::createFromGeoJSONString(json_encode($geometry), $srid);
                }

                if(!empty($geometries) && $geometries[0] instanceof ShapeZ\GeometryZ) {
                    //return new ShapeZ\GeometryCollectionZ($geometries, $srid);
                }
                return new Shape\GeometryCollection($geometries, $srid);

            default:
                throw new \InvalidArgumentException('Unsupported GeoJSON type: ' . $geoJsonData['type']);
        }

        throw new \InvalidArgumentException('Invalid number of coordinates for the given geometry type.');
    }

    public static function createFromGeoEWKTString(string $ewktString): Shape\Geometry|ShapeZ\GeometryZ
    {
        return Shape\Factory::createFromGeoEWKTString($ewktString);
    }
}