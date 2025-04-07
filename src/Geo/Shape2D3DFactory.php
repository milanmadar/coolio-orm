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
        if (!isset($geoJsonData['type'], $geoJsonData['coordinates'])) {
            throw new \InvalidArgumentException('GeoJSON must contain "type" and "coordinates".');
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

//            case 'Polygon':
//                // For polygons, we would similarly check for 2D or 3D based on coordinates
//                if (count($geoJsonData['coordinates'][0][0]) === 2) {
//                    // 2D Polygon
//                    return Polygon::createFromGeoJSONData($geoJsonData, $srid);
//                } elseif (count($geoJsonData['coordinates'][0][0]) === 3) {
//                    // 3D Polygon
//                    return PolygonZ::createFromGeoJSONData($geoJsonData, $srid);
//                }
//                break;
//
//            case 'MultiPoint':
//                // Check MultiPoint geometry (all points should be 2D or 3D)
//                if (count($geoJsonData['coordinates'][0]) === 2) {
//                    // 2D MultiPoint
//                    return MultiPoint::createFromGeoJSONData($geoJsonData, $srid);
//                } elseif (count($geoJsonData['coordinates'][0]) === 3) {
//                    // 3D MultiPoint
//                    return MultiPointZ::createFromGeoJSONData($geoJsonData, $srid);
//                }
//                break;
//
//            case 'MultiLineString':
//                // Check MultiLineString geometry (all points should be 2D or 3D)
//                if (count($geoJsonData['coordinates'][0][0]) === 2) {
//                    // 2D MultiLineString
//                    return MultiLineString::createFromGeoJSONData($geoJsonData, $srid);
//                } elseif (count($geoJsonData['coordinates'][0][0]) === 3) {
//                    // 3D MultiLineString
//                    return MultiLineStringZ::createFromGeoJSONData($geoJsonData, $srid);
//                }
//                break;
//
//            case 'MultiPolygon':
//                // Check MultiPolygon geometry (all points should be 2D or 3D)
//                if (count($geoJsonData['coordinates'][0][0][0]) === 2) {
//                    // 2D MultiPolygon
//                    return MultiPolygon::createFromGeoJSONData($geoJsonData, $srid);
//                } elseif (count($geoJsonData['coordinates'][0][0][0]) === 3) {
//                    // 3D MultiPolygon
//                    return MultiPolygonZ::createFromGeoJSONData($geoJsonData, $srid);
//                }
//                break;

//            case 'GeometryCollection':
//                // For GeometryCollection, we would check for 2D or 3D types inside the collection
//                // For simplicity, we'll assume all geometries inside are consistent in terms of 2D or 3D.
//                // This part would require more careful checking in a real-world scenario.
//                $geometries = [];
//                foreach ($geoJsonData['geometries'] as $geometry) {
//                    $geometries[] = self::createFromGeoJSONString(json_encode($geometry), $srid);
//                }
//
//                return new GeometryCollection($geometries, $srid);

            default:
                throw new \InvalidArgumentException('Unsupported GeoJSON type: ' . $geoJsonData['type']);
        }

        throw new \InvalidArgumentException('Invalid number of coordinates for the given geometry type.');
    }

    public static function createFromGeoEWKTString(string $ewktString): Shape\Geometry|ShapeZ\GeometryZ
    {
        return ShapeZ\PointZ::createFromGeoEWKTString($ewktString);
    }
}