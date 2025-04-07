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
                $coordCount = count($geoJsonData['coordinates'][0][0]);
                if ($coordCount == 2) {
                    return Shape\MultiLineString::createFromGeoJSONData($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\MultiLineStringZ::createFromGeoJSONData($geoJsonData, $srid);
                }
                break;

            case 'MultiPolygon':
                $coordCount = count($geoJsonData['coordinates'][0][0][0]);
                if ($coordCount == 2) {
                    return Shape\MultiPolygon::createFromGeoJSONData($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\MultiPolygonZ::createFromGeoJSONData($geoJsonData, $srid);
                }
                break;

            case 'GeometryCollection':
                $geometries = [];
                foreach ($geoJsonData['geometries'] as $geometry) {
                    $geometries[] = self::createFromGeoJSONString(json_encode($geometry), $srid);
                }

                if(!empty($geometries) && $geometries[0] instanceof ShapeZ\GeometryZ) {
                    return new ShapeZ\GeometryCollectionZ($geometries, $srid);
                }
                return new Shape\GeometryCollection($geometries, $srid);

            default:
                throw new \InvalidArgumentException('Unsupported GeoJSON type: ' . $geoJsonData['type']);
        }

        throw new \InvalidArgumentException('Invalid number of coordinates for the given geometry type.');
    }

    public static function createFromGeoEWKTString(string $ewktString): Shape\Geometry|ShapeZ\GeometryZ
    {
        // Quick check for SRID part
        if (!str_starts_with($ewktString, 'SRID=')) {
            throw new \InvalidArgumentException('Invalid EWKT string: Missing SRID.');
        }

        // Extract the geometry type and coordinates
        $typePattern = '/SRID=\d+;([A-Z]+)(\s*Z)?\s*\((.+)\)/s';
        if (!preg_match($typePattern, $ewktString, $matches)) {
            throw new \InvalidArgumentException('Invalid EWKT string: Cannot detect geometry type and coordinates.');
        }

        $type = strtoupper(trim($matches[1]));

        // is it 3D coordinates?
        $explicitZ = !empty(trim($matches[2]));
        if($explicitZ) {
            $is3D = true;
        } else {
            $coordinateString = trim($matches[3]);
            // Find the first coordinate tuple (split by comma, then split by space)
            $firstTuple = explode(',', $coordinateString)[0];
            $dimensions = preg_split('/\s+/', trim($firstTuple));
            $is3D = count($dimensions) == 3;
        }

//        if(!$is3D) {
//            return Shape\Factory::createFromGeoEWKTString($ewktString);
//        }

        // Now switch based on the type
        switch ($type) {
            case 'CIRCULARSTRING':
                return $is3D
                    ? ShapeZ\CircularStringZ::createFromGeoEWKTString($ewktString)
                    :  Shape\CircularString::createFromGeoEWKTString($ewktString);

            case 'COMPOUNDCURVE':
                return $is3D
                    ? ShapeZ\CompoundCurveZ::createFromGeoEWKTString($ewktString)
                    : Shape\CompoundCurve::createFromGeoEWKTString($ewktString);

            case 'CURVEPOLYGON':
                return $is3D
                    ? ShapeZ\CurvePolygonZ::createFromGeoEWKTString($ewktString)
                    : Shape\CurvePolygon::createFromGeoEWKTString($ewktString);

            case 'MULTICURVE':
                return $is3D
                    ? ShapeZ\MultiCurveZ::createFromGeoEWKTString($ewktString)
                    : Shape\MultiCurve::createFromGeoEWKTString($ewktString);

            case 'MULTISURFACE':
                return Shape\MultiSurface::createFromGeoEWKTString($ewktString);
//                return $is3D
//                    ? ShapeZ\MultiSurfaceZ::createFromGeoEWKTString($ewktString)
//                    : Shape\MultiSurface::createFromGeoEWKTString($ewktString);

            default:
                throw new \InvalidArgumentException('Unsupported EWKT type: ' . $type);
        }
    }
}