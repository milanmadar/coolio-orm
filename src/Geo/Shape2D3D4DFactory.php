<?php

namespace Milanmadar\CoolioORM\Geo;

class Shape2D3D4DFactory
{
    /**
     * Creates a Geometry object from a GeoJSON string.
     *
     * @param string $geoJsonStr The GeoJSON string to parse.
     * @param int|null $srid Optional SRID to assign to the geometry.
     * @return Shape2D\AbstractShape2D|ShapeZ\AbstractShapeZ|ShapeZM\AbstractShapeZM The created geometry (Point, PointZ, LineString, LineStringZ, etc.).
     * @throws \InvalidArgumentException If the GeoJSON is invalid or unsupported.
     */
    public static function createFromGeoJSONString(string $geoJsonStr, int|null $srid = null): Shape2D\AbstractShape2D|ShapeZ\AbstractShapeZ|ShapeZM\AbstractShapeZM
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        $geoJsonData = json_decode($geoJsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid GeoJSON string.');
        }

        return self::createFromGeoJSON($geoJsonData, $srid);
    }

    /**
     * @param array<string, mixed> $geoJsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return Shape2D\AbstractShape2D|ShapeZ\AbstractShapeZ|ShapeZM\AbstractShapeZM
     */
    public static function createFromGeoJSON(array $geoJsonData, int|null $srid = null): Shape2D\AbstractShape2D|ShapeZ\AbstractShapeZ|ShapeZM\AbstractShapeZM
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        // Check the type of geometry
        if (!isset($geoJsonData['type'])) {
            throw new \InvalidArgumentException('GeoJSON must contain "type".');
        }

        switch ($geoJsonData['type']) {
            case 'Point':
                $coordCount = count($geoJsonData['coordinates']);
                if ($coordCount == 2) {
                    return Shape2D\Point::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\PointZ::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 4) {
                    return ShapeZM\PointZM::createFromGeoJSON($geoJsonData, $srid);
                }
                break;

            case 'LineString':
                $coordCount = count($geoJsonData['coordinates'][0]);
                if ($coordCount == 2) {
                    return Shape2D\LineString::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\LineStringZ::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 4) {
                    return ShapeZM\LineStringZM::createFromGeoJSON($geoJsonData, $srid);
                }
                break;

            case 'Polygon':
                $coordCount = count($geoJsonData['coordinates'][0][0]);
                if ($coordCount == 2) {
                    return Shape2D\Polygon::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\PolygonZ::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 4) {
                    return ShapeZM\PolygonZM::createFromGeoJSON($geoJsonData, $srid);
                }
                break;

            case 'MultiPoint':
                $coordCount = count($geoJsonData['coordinates'][0]);
                if ($coordCount == 2) {
                    return Shape2D\MultiPoint::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\MultiPointZ::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 4) {
                    return ShapeZM\MultiPointZM::createFromGeoJSON($geoJsonData, $srid);
                }
                break;

            case 'MultiLineString':
                $coordCount = count($geoJsonData['coordinates'][0][0]);
                if ($coordCount == 2) {
                    return Shape2D\MultiLineString::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\MultiLineStringZ::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 4) {
                    return ShapeZM\MultiLineStringZM::createFromGeoJSON($geoJsonData, $srid);
                }
                break;

            case 'MultiPolygon':
                $coordCount = count($geoJsonData['coordinates'][0][0][0]);
                if ($coordCount == 2) {
                    return Shape2D\MultiPolygon::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 3) {
                    return ShapeZ\MultiPolygonZ::createFromGeoJSON($geoJsonData, $srid);
                } elseif ($coordCount == 4) {
                    return ShapeZM\MultiPolygonZM::createFromGeoJSON($geoJsonData, $srid);
                }
                break;

            case 'GeometryCollection':
                $geometries = [];
                foreach ($geoJsonData['geometries'] as $geometry) {
                    $geometries[] = self::createFromGeoJSONString((string)json_encode($geometry), $srid);
                }

                if(!empty($geometries)) {
                    if($geometries[0] instanceof ShapeZM\AbstractShapeZM) {
                        /** @var array<ShapeZM\AbstractShapeZM> $geometries */
                        return new ShapeZM\GeometryCollectionZM($geometries, $srid);
                    }
                    if($geometries[0] instanceof ShapeZ\AbstractShapeZ) {
                        /** @var array<ShapeZ\AbstractShapeZ> $geometries */
                        return new ShapeZ\GeometryCollectionZ($geometries, $srid);
                    }
                }

                /** @var array<Shape2D\AbstractShape2D> $geometries */
                return new Shape2D\GeometryCollection($geometries, $srid);

            case 'Feature':
                return \Milanmadar\CoolioORM\Geo\Feature::createFromGeoJSON($geoJsonData, $srid);

            case 'FeatureCollection':
                return new \Milanmadar\CoolioORM\Geo\FeatureCollection(
                    array_map(
                        fn(array $f) => \Milanmadar\CoolioORM\Geo\Feature::createFromGeoJSON($f, $srid),
                        $geoJsonData['features'] ?? []
                    ),
                    $srid
                );

            default:
                throw new \InvalidArgumentException('Unsupported GeoJSON type: ' . $geoJsonData['type']);
        }

        throw new \InvalidArgumentException('Invalid number of coordinates for the given geometry type.');
    }

    /**
     * @param string $ewktString
     * @return Shape2D\AbstractShape2D|ShapeZ\AbstractShapeZ|ShapeZM\AbstractShapeZM
     */
    public static function createFromGeoEWKTString(string $ewktString): Shape2D\AbstractShape2D|ShapeZ\AbstractShapeZ|ShapeZM\AbstractShapeZM
    {
        // Quick check for SRID part
        if (!str_starts_with($ewktString, 'SRID=')) {
            throw new \InvalidArgumentException('Invalid EWKT string: Missing SRID.');
        }

        // Extract the geometry type and coordinates
        $ewktStringTest = $ewktString;
        $ewktStringTest = str_replace([' ZM (',' ZM(','ZM ('], 'ZM(', $ewktStringTest);
        $ewktStringTest = str_replace([' Z (',' Z(','Z ('], 'Z(', $ewktStringTest);
        $ewktStringTest = str_replace(' (', '(', $ewktStringTest);
        $ewktStringTest = str_replace(' ,', ',', $ewktStringTest);
        $ewktStringTest = str_replace('; ', ';', $ewktStringTest);
        $typePattern = '/SRID=\d+;([A-Z]+)(\s*ZM?)?\s*\((.+)\)/s';
        if (!preg_match($typePattern, $ewktStringTest, $matches)) {
            throw new \InvalidArgumentException('Invalid EWKT string: Cannot detect geometry type and coordinates.');
        }
        unset($ewktStringTest);

        $type = strtoupper(trim($matches[1]));

        // 2D or 3D or 4D
        $coordinateString = trim($matches[3]);
        // Find the first coordinate tuple (split by comma, then split by space)
        $firstTuple = explode(',', $coordinateString)[0];
        /** @var array<string> $dimensions */
        $dimensions = preg_split('/\s+/', trim($firstTuple));
        $dims = count($dimensions);

        // Now switch based on the type
        switch ($type) {
            case 'CIRCULARSTRING':
            case 'CIRCULARSTRINGZ':
            case 'CIRCULARSTRINGZM':
                return match($dims) {
                    4 => ShapeZM\CircularStringZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\CircularStringZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\CircularString::createFromGeoEWKTString($ewktString),
                };

            case 'COMPOUNDCURVE':
            case 'COMPOUNDCURVEZ':
            case 'COMPOUNDCURVEZM':
                return match($dims) {
                    4 => ShapeZM\CompoundCurveZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\CompoundCurveZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\CompoundCurve::createFromGeoEWKTString($ewktString),
                };

            case 'CURVEPOLYGON':
            case 'CURVEPOLYGONZ':
            case 'CURVEPOLYGONZM':
                return match($dims) {
                    4 => ShapeZM\CurvePolygonZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\CurvePolygonZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\CurvePolygon::createFromGeoEWKTString($ewktString),
                };

            case 'MULTICURVE':
            case 'MULTICURVEZ':
            case 'MULTICURVEZM':
                return match($dims) {
                    4 => ShapeZM\MultiCurveZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\MultiCurveZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\MultiCurve::createFromGeoEWKTString($ewktString),
                };

            case 'MULTILINESTRING':
            case 'MULTILINESTRINGZ':
            case 'MULTILINESTRINGZM':
                return match($dims) {
                    4 => ShapeZM\MultiLineStringZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\MultiLineStringZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\MultiLineString::createFromGeoEWKTString($ewktString),
                };

            case 'GEOMETRYCOLLECTION':
            case 'GEOMETRYCOLLECTIONZ':
            case 'GEOMETRYCOLLECTIONZM':
                return match($dims) {
                    4 => ShapeZM\GeometryCollectionZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\GeometryCollectionZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\GeometryCollection::createFromGeoEWKTString($ewktString),
                };

            case 'MULTIPOLYGON':
            case 'MULTIPOLYGONZ':
            case 'MULTIPOLYGONZM':
                return match($dims) {
                    4 => ShapeZM\MultiPolygonZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\MultiPolygonZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\MultiPolygon::createFromGeoEWKTString($ewktString),
                };

            case 'POLYGON':
            case 'POLYGONZ':
            case 'POLYGONZM':
                return match($dims) {
                    4 => ShapeZM\PolygonZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\PolygonZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\Polygon::createFromGeoEWKTString($ewktString),
                };

            case 'LINESTRING':
            case 'LINESTRINGZ':
            case 'LINESTRINGZM':
                return match($dims) {
                    4 => ShapeZM\LineStringZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\LineStringZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\LineString::createFromGeoEWKTString($ewktString),
                };

            case 'MULTIPOINT':
            case 'MULTIPOINTZ':
            case 'MULTIPOINTZM':
                return match($dims) {
                    4 => ShapeZM\MultiPointZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\MultiPointZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\MultiPoint::createFromGeoEWKTString($ewktString),
                };

            case 'POINT':
            case 'POINTZ':
            case 'POINTZM':
                return match($dims) {
                    4 => ShapeZM\PointZM::createFromGeoEWKTString($ewktString),
                    3 => ShapeZ\PointZ::createFromGeoEWKTString($ewktString),
                    default => Shape2D\Point::createFromGeoEWKTString($ewktString),
                };

            default:
                throw new \InvalidArgumentException('Unsupported EWKT type: ' . $type);
        }
    }
}