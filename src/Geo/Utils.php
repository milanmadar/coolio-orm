<?php

namespace Milanmadar\CoolioORM\Geo;

use Doctrine\DBAL\Connection;
use Milanmadar\CoolioORM\Manager;

class Utils
{
    /**
     * Automatically calculates the correct UTM SRID for a WGS84 coordinate.
     * @param float $lon WGS84 Longitude
     * @param float $lat WGS84 Latitude
     * @return int The appropriate EPSG SRID (326XX for North, 327XX for South)
     */
    public static function getUtmSridFromWGS(float $lon, float $lat): int
    {
        if ($lat < -90 || $lat > 90) {
            // swap them
            $temp = $lat;
            $lat = $lon;
            $lon = $temp;
        }

        // Formula: Zone = floor((lon + 180) / 6) + 1
        $zone = floor(($lon + 180) / 6) + 1;

        // EPSG 32600 block for Northern Hemisphere, 32700 for Southern
        return (int)(
            ($lat >= 0) ? (32600 + $zone) : (32700 + $zone)
        );
    }

    /**
     * @template T of AbstractShape
     *
     * @param T $geom
     * @param int $targetSrid
     * @param Manager|Connection $dbOrMgr
     * @return T
     */
    public static function transformGeomToSrid(AbstractShape $geom, int $targetSrid, Manager|Connection $dbOrMgr): AbstractShape
    {
        $stTransform = GeoFunctions::ST_Transform($geom, $targetSrid);
        $sql = "SELECT ST_AsEWKT(".$stTransform.")";

        if($dbOrMgr instanceof Manager) {
            $db = $dbOrMgr->getDb();
        } else {
            $db = $dbOrMgr;
        }

        $geomEWKT = $db->executeQuery($sql)->fetchOne();

        /* @phpstan-ignore-next-line */
        return Shape2D3D4DFactory::createFromGeoEWKTString($geomEWKT);
    }

    /**
     * 2 Points are optimized to stay in PHP (whenever possible)
     * @param AbstractShape $geom1
     * @param AbstractShape $geom2
     * @param Manager|Connection $dbOrMgr
     * @param int $roundToPrecision optional
     * @return float
     */
    public static function getDistanceInMeters(AbstractShape $geom1, AbstractShape $geom2, Manager|Connection $dbOrMgr, int $roundToPrecision = -1): float
    {
        // in some cases, PHP is enough
        if($geom1 instanceof Shape2D\Point
        || $geom1 instanceof ShapeZ\PointZ
        || $geom1 instanceof ShapeZM\PointZM
        ) {
            if($geom2 instanceof Shape2D\Point
            || $geom2 instanceof ShapeZ\PointZ
            || $geom2 instanceof ShapeZM\PointZM
            ) {
                $srid1 = $geom1->getSRID();
                $srid2 = $geom2->getSRID();
                if($srid1 == $srid2)
                {
                    // WGS84 (Haversine)
                    if($srid1 == 4326 && $srid2 == 4326) {
                        return self::getDistanceInMetersPointsWGS($geom1, $geom2, $roundToPrecision);
                    }

                    // UTM (Pythagoras)
                    $isUtmNorth = ($srid1 >= 32601 && $srid1 <= 32660);
                    $isUtmSouth = ($srid1 >= 32701 && $srid1 <= 32760);
                    if ($isUtmNorth || $isUtmSouth) {
                        // This is the Pythagoras function we finalized earlier
                        return self::getDistanceInMetersPoints($geom1, $geom2, $roundToPrecision);
                    }
                }
            }
        }

        $stDistance = GeoFunctions::ST_Distance(
            GeoFunctions::geography(
                GeoFunctions::ST_Transform($geom1, 4326)
            ),
            GeoFunctions::geography(
                GeoFunctions::ST_Transform($geom2, 4326)
            )
        );
        $sql = "SELECT ".$stDistance;

        if($dbOrMgr instanceof Manager) {
            $db = $dbOrMgr->getDb();
        } else {
            $db = $dbOrMgr;
        }

        $v = (float)$db->executeQuery($sql)->fetchOne();
        if($roundToPrecision > -1) {
            return round($v, $roundToPrecision);
        }
        return $v;
    }

    /**
     * @param Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $geom1
     * @param Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $geom2
     * @param int $roundToPrecision
     * @return float
     */
    private static function getDistanceInMetersPoints(Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $geom1, Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $geom2, int $roundToPrecision = -1): float
    {
        $srid1 = $geom1->getSrid();
        $srid2 = $geom2->getSrid();

        if($srid1 != $srid2) {
            throw new \InvalidArgumentException("getDistanceInMetersPoints() Both points must be in the same SRID. Use getDistanceInMeters() function (that will use postgres)");
        }

        // Haversine
        if($srid1 == 4326) {
            return self::getDistanceInMetersPointsWGS($geom1, $geom2, $roundToPrecision);
        }

        $x1 = $geom1->getX();
        $y1 = $geom1->getY();
        $x2 = $geom2->getX();
        $y2 = $geom2->getY();
        $z1 = ($geom1 instanceof ShapeZ\PointZ || $geom1 instanceof ShapeZM\PointZM) ? $geom1->getZ() : null;
        $z2 = ($geom2 instanceof ShapeZ\PointZ || $geom2 instanceof ShapeZM\PointZM) ? $geom2->getZ() : null;

        $dx = $x2 - $x1;
        $dy = $y2 - $y1;

        if(isset($z1) && !isset($z2)) {
            $z2 = $z1;
        } elseif(!isset($z1) && isset($z2)) {
            $z1 = $z2;
        }

        if(isset($z1)) {
            $dz = $z2 - $z1;
            $dist = sqrt($dx**2 + $dy**2 + $dz**2);
        } else {
            $dist = sqrt($dx**2 + $dy**2);
        }

        return ($roundToPrecision > -1)
            ? round($dist, $roundToPrecision)
            : $dist;
    }

    /**
     * Haversine formula
     * @param Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $geom1
     * @param Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $geom2
     * @param int $roundToPrecision Optional. Default is no rounding
     * @return float
     */
    private static function getDistanceInMetersPointsWGS(Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $geom1, Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $geom2, int $roundToPrecision = -1): float
    {
        $earthRadius = 6371000; // Radius in meters

        $lon1 = $geom1->getX();
        $lat1 = $geom1->getY();
        $lon2 = $geom2->getX();
        $lat2 = $geom2->getY();

        // Horizontal Surface Distance
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $horizontalMeters = $earthRadius * $c;

        // Altitude (Z)
        $z1 = ($geom1 instanceof ShapeZ\PointZ || $geom1 instanceof ShapeZM\PointZM) ? $geom1->getZ() : null;
        $z2 = ($geom2 instanceof ShapeZ\PointZ || $geom2 instanceof ShapeZM\PointZM) ? $geom2->getZ() : null;

        if (isset($z1) && isset($z2)) {
            $dz = $z2 - $z1;
            // Combine Horizontal and Vertical using Pythagoras
            $totalMeters = sqrt($horizontalMeters**2 + $dz**2);
        } else {
            $totalMeters = $horizontalMeters;
        }

        return ($roundToPrecision > -1)
            ? round($totalMeters, $roundToPrecision)
            : $totalMeters;
    }

    /**
     * @param AbstractShape $geom
     * @param Manager|Connection $dbOrMgr
     * @param int $roundToPrecision optional
     * @return float
     */
    public static function getLengthInMeter(AbstractShape $geom, Manager|Connection $dbOrMgr, int $roundToPrecision = -1): float
    {
        // point, lengths in zero
        if($geom instanceof Shape2D\Point
        || $geom instanceof ShapeZ\PointZ
        || $geom instanceof ShapeZM\PointZM
        || $geom instanceof Shape2D\MultiPoint
        || $geom instanceof ShapeZ\MultiPointZ
        || $geom instanceof ShapeZM\MultiPointZM)
        {
            return 0;
        }

        // line and multiline and curved lines
        if($geom instanceof Shape2D\LineString
        || $geom instanceof ShapeZ\LineStringZ
        || $geom instanceof ShapeZM\LineStringZM
        || $geom instanceof Shape2D\MultiLineString
        || $geom instanceof ShapeZ\MultiLineStringZ
        || $geom instanceof ShapeZM\MultiLineStringZM
        || $geom instanceof Shape2D\CircularString
        || $geom instanceof ShapeZ\CircularStringZ
        || $geom instanceof ShapeZM\CircularStringZM
        || $geom instanceof Shape2D\CompoundCurve
        || $geom instanceof ShapeZ\CompoundCurveZ
        || $geom instanceof ShapeZM\CompoundCurveZM)
        {
            $sql = "SELECT ST_Length(".GeoFunctions::ST_GeomFromEWKT_geom($geom)."::geography)";
        }
        // polygon and multipolygon and curved polygons
        elseif($geom instanceof Shape2D\Polygon
        || $geom instanceof ShapeZ\PolygonZ
        || $geom instanceof ShapeZM\PolygonZM
        || $geom instanceof Shape2D\MultiPolygon
        || $geom instanceof ShapeZ\MultiPolygonZ
        || $geom instanceof ShapeZM\MultiPolygonZM
        || $geom instanceof Shape2D\CurvePolygon
        || $geom instanceof ShapeZ\CurvePolygonZ
        || $geom instanceof ShapeZM\CurvePolygonZM)
        {
            $sql = "SELECT ST_Perimeter(".GeoFunctions::ST_GeomFromEWKT_geom($geom)."::geography)";
        }
        else {
            throw new \InvalidArgumentException("Geo\Utils::getLengthInMeter() doesnt support ".get_class($geom));
        }

        if($dbOrMgr instanceof Manager) {
            $db = $dbOrMgr->getDb();
        } else {
            $db = $dbOrMgr;
        }

        $v = (float)$db->executeQuery($sql)->fetchOne();
        if($roundToPrecision > -1) {
            return round($v, $roundToPrecision);
        }
        return $v;
    }

    /**
     * @param AbstractShape $line
     * @param Manager|Connection $dbOrMgr
     * @param 'start'|'end' $untilStartOrEnd 'start'|'end'
     * @param int $roundToPrecision optional
     * @return float
     */
    public static function getLength_fromPointInLine_tillEndOfLine_InMeter(AbstractShape $line, Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $pointOnLine, string $untilStartOrEnd, Manager|Connection $dbOrMgr, int $roundToPrecision = -1): float
    {
        if(!($line instanceof Shape2D\LineString)
        && !($line instanceof ShapeZ\LineStringZ)
        && !($line instanceof ShapeZM\LineStringZM)
        && !($line instanceof Shape2D\CircularString)
        && !($line instanceof ShapeZ\CircularStringZ)
        && !($line instanceof ShapeZM\CircularStringZM)
        ) {
            throw new \InvalidArgumentException("Geo\Utils::getLength_fromPointInLine_tillEndOfLine_InMeter() only supports LineString, LineStringZ, LineStringZM, CircularString, CircularStringZ and CircularStringZM");
        }

        if ($line->getSRID() != $pointOnLine->getSRID()) {
            throw new \InvalidArgumentException("Geo\Utils::getLength_fromPointInLine_tillEndOfLine_InMeter() SRIDs must match");
        }

        $is3D = ($line instanceof ShapeZ\LineStringZ
            || $line instanceof ShapeZM\LineStringZM
            || $line instanceof ShapeZ\CircularStringZ
            || $line instanceof ShapeZM\CircularStringZM
        );

        $inputSrid = $line->getSRID();

        $lineEwkt = GeoFunctions::ST_GeomFromEWKT_geom($line);
        if ($line instanceof Shape2D\CircularString || $line instanceof ShapeZ\CircularStringZ || $line instanceof ShapeZM\CircularStringZM) {
            $lineEwkt = "ST_CurveToLine(" . $lineEwkt . ")";
        }

        $pointEwkt = GeoFunctions::ST_GeomFromEWKT_geom($pointOnLine);

        // Point Location Logic (Always 2D)
        $locateSql = "ST_LineLocatePoint(ST_Force2D(line), ST_Force2D(p))";
        $startFrac = ($untilStartOrEnd === 'start') ? '0.0' : $locateSql;
        $endFrac   = ($untilStartOrEnd === 'start') ? $locateSql : '1.0';

        // Length Calculation Strategy
        if ($is3D) {
            if ($inputSrid === 4326) {
                // Case: 3D WGS84 -> Must project to UTM for valid 3D meters
                $utmSrid = self::getUtmSridFromWGS($pointOnLine->getX(), $pointOnLine->getY());
                $lengthSql = "ST_3DLength(ST_Transform(sub, $utmSrid))";
            } else {
                // Case: 3D Projected -> Assume input units are already meters
                $lengthSql = "ST_3DLength(sub)";
            }
        } else {
            if ($inputSrid === 4326) {
                // Case: 2D WGS84 -> Use Geography (Spheroid) for high precision
                $lengthSql = "ST_Length(sub::geography)";
            } else {
                // Case: 2D Projected -> Use Geometry (Planar) to match user projection
                $lengthSql = "ST_Length(sub)";
            }
        }

        $sql = "
            WITH data AS (
                SELECT $lineEwkt AS line, $pointEwkt AS p
            ),
            segment AS (
                SELECT ST_LineSubstring(line, $startFrac, $endFrac) as sub
                FROM data
            )
            SELECT $lengthSql FROM segment
        ";

        $db = ($dbOrMgr instanceof Manager) ? $dbOrMgr->getDb() : $dbOrMgr;
        $v = (float)$db->executeQuery($sql)->fetchOne();

        return ($roundToPrecision > -1) ? round($v, $roundToPrecision) : $v;
    }

    /**
     * @template T of Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM
     *
     * @param AbstractShape $returnPointOnThisGeom
     * @param T $closestToThisPoint
     * @param Manager|Connection $dbOrMgr
     * @return T
     */
    public static function getClosestPoint(AbstractShape $returnPointOnThisGeom, Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $closestToThisPoint, Manager|Connection $dbOrMgr): AbstractShape
    {
        // point, the closes point is itself
        if($returnPointOnThisGeom instanceof Shape2D\Point
        || $returnPointOnThisGeom instanceof ShapeZ\PointZ
        || $returnPointOnThisGeom instanceof ShapeZM\PointZM)
        {
            /** @var T of Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM */
            return clone $returnPointOnThisGeom;
        }

        // multipoint
        if($returnPointOnThisGeom instanceof Shape2D\MultiPoint
        || $returnPointOnThisGeom instanceof ShapeZ\MultiPointZ
        || $returnPointOnThisGeom instanceof ShapeZM\MultiPointZM)
        {
            $sql = "
                SELECT ST_AsEWKT(
                    ST_ClosestPoint(
                        target,
                        ST_Transform(p, ST_SRID(target))
                    )
                )
                FROM (
                    SELECT 
                        ".GeoFunctions::ST_GeomFromEWKT_geom($returnPointOnThisGeom)." AS target,
                        ".GeoFunctions::ST_GeomFromEWKT_geom($closestToThisPoint)." AS p
                ) AS data;
            ";
        }
        // line and multiline
        elseif($returnPointOnThisGeom instanceof Shape2D\LineString
        || $returnPointOnThisGeom instanceof ShapeZ\LineStringZ
        || $returnPointOnThisGeom instanceof ShapeZM\LineStringZM
        || $returnPointOnThisGeom instanceof Shape2D\MultiLineString
        || $returnPointOnThisGeom instanceof ShapeZ\MultiLineStringZ
        || $returnPointOnThisGeom instanceof ShapeZM\MultiLineStringZM)
        {
            $sql = "
                SELECT ST_AsEWKT(
                        ST_LineInterpolatePoint(
                            line,
                            ST_LineLocatePoint(line, ST_Transform(p, ST_SRID(line)))
                        )
                    )
                FROM (
                    SELECT 
                        ".GeoFunctions::ST_GeomFromEWKT_geom($returnPointOnThisGeom)." AS line,
                        ".GeoFunctions::ST_GeomFromEWKT_geom($closestToThisPoint)." AS p
                ) AS data
            ";
        }
        // curved geoms
        elseif($returnPointOnThisGeom instanceof Shape2D\CircularString
        || $returnPointOnThisGeom instanceof ShapeZ\CircularStringZ
        || $returnPointOnThisGeom instanceof ShapeZM\CircularStringZM
        || $returnPointOnThisGeom instanceof Shape2D\CompoundCurve
        || $returnPointOnThisGeom instanceof ShapeZ\CompoundCurveZ
        || $returnPointOnThisGeom instanceof ShapeZM\CompoundCurveZM
        || $returnPointOnThisGeom instanceof Shape2D\CurvePolygon
        || $returnPointOnThisGeom instanceof ShapeZ\CurvePolygonZ
        || $returnPointOnThisGeom instanceof ShapeZM\CurvePolygonZM)
        {
            $sql = "
                SELECT ST_AsEWKT(
                        ST_LineInterpolatePoint(
                            line,
                            ST_LineLocatePoint(line, ST_Transform(p, ST_SRID(line)))
                        )
                    )
                FROM (
                    SELECT 
                        ST_CurveToLine(".GeoFunctions::ST_GeomFromEWKT_geom($returnPointOnThisGeom).") AS line,
                        ".GeoFunctions::ST_GeomFromEWKT_geom($closestToThisPoint)." AS p
                ) AS data
            ";
        }
        // polygon and multipolygon
        elseif($returnPointOnThisGeom instanceof Shape2D\Polygon
        || $returnPointOnThisGeom instanceof ShapeZ\PolygonZ
        || $returnPointOnThisGeom instanceof ShapeZM\PolygonZM
        || $returnPointOnThisGeom instanceof Shape2D\MultiPolygon
        || $returnPointOnThisGeom instanceof ShapeZ\MultiPolygonZ
        || $returnPointOnThisGeom instanceof ShapeZM\MultiPolygonZM)
        {
            $sql = "
                SELECT ST_AsEWKT(
                    ST_LineInterpolatePoint(
                        boundary, 
                        ST_LineLocatePoint(boundary, ST_Transform(p, ST_SRID(boundary)))
                    )
                )
                FROM (
                    SELECT 
                        ST_Boundary(".GeoFunctions::ST_GeomFromEWKT_geom($returnPointOnThisGeom).") AS boundary,
                        ".GeoFunctions::ST_GeomFromEWKT_geom($closestToThisPoint)." AS p
                ) AS data;
            ";
        }
        else {
            throw new \InvalidArgumentException("Geo\Utils::getClosestPoint() doesnt support ".get_class($returnPointOnThisGeom));
        }

        if($dbOrMgr instanceof Manager) {
            $db = $dbOrMgr->getDb();
        } else {
            $db = $dbOrMgr;
        }

        $geomEWKT = $db->executeQuery($sql)->fetchOne();

        /* @phpstan-ignore-next-line */
        return Shape2D3D4DFactory::createFromGeoEWKTString($geomEWKT);
    }

    /**
     * Clone
     * If needed, it will add 1 more point to match the first one. MultiLines will be turned into single polygons
     * @param AbstractShape $line
     * @return Shape2D\Polygon|ShapeZ\PolygonZ|ShapeZM\PolygonZM
     */
    public static function lineToPolygon(AbstractShape $line): Shape2D\Polygon|ShapeZ\PolygonZ|ShapeZM\PolygonZM
    {
        if($line instanceof Shape2D\Polygon
        || $line instanceof ShapeZ\PolygonZ
        || $line instanceof ShapeZM\PolygonZM)
        {
            return clone $line;
        }

        if($line instanceof Shape2D\MultiLineString
        || $line instanceof ShapeZ\MultiLineStringZ
        || $line instanceof ShapeZM\MultiLineStringZM)
        {
            $pointsOfSingleLine = [];
            $lineStrings = array_values( $line->getLineStrings() );
            foreach ($lineStrings as $lineString) {
                $lineString = clone $lineString;
                $points = $lineString->getPoints();
                $pointsOfSingleLine = array_merge( $pointsOfSingleLine, $points );
            }

            /** @var Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $lastPoint */
            $lastPoint = end($pointsOfSingleLine);
            if(!$lastPoint->equals($pointsOfSingleLine[0])) {
                $pointsOfSingleLine[] = clone $pointsOfSingleLine[0];
            }

            if($line instanceof Shape2D\MultiLineString) {
                /** @var array<Shape2D\Point> $pointsOfSingleLine */
                $line = new Shape2D\LineString($pointsOfSingleLine, $line->getSRID());
                return new Shape2D\Polygon([$line], $line->getSRID());
            }
            elseif($line instanceof ShapeZ\MultiLineStringZ) {
                /** @var array<ShapeZ\PointZ> $pointsOfSingleLine */
                $line = new ShapeZ\LineStringZ($pointsOfSingleLine, $line->getSRID());
                return new ShapeZ\PolygonZ([$line], $line->getSRID());
            }
            elseif($line instanceof ShapeZM\MultiLineStringZM) {
                /** @var array<ShapeZM\PointZM> $pointsOfSingleLine */
                $line = new ShapeZM\LineStringZM($pointsOfSingleLine, $line->getSRID());
                return new ShapeZM\PolygonZM([$line], $line->getSRID());
            }
        }

        if($line instanceof Shape2D\LineString
        || $line instanceof ShapeZ\LineStringZ
        || $line instanceof ShapeZM\LineStringZM)
        {
            $line = clone $line;
            $points = $line->getPoints();

            /** @var Shape2D\Point|ShapeZ\PointZ|ShapeZM\PointZM $lastPoint */
            $lastPoint = end($points);
            if(!$lastPoint->equals($points[0])) {
                $points[] = clone $points[0];
            }

            $line->setPoints($points);

            if($line instanceof Shape2D\LineString) {
                return new Shape2D\Polygon([$line], $line->getSRID());
            } elseif($line instanceof ShapeZ\LineStringZ) {
                return new ShapeZ\PolygonZ([$line], $line->getSRID());
            } elseif($line instanceof ShapeZM\LineStringZM) {
                return new ShapeZM\PolygonZM([$line], $line->getSRID());
            }
        }

        throw new \InvalidArgumentException("Geo\Utils::lineToPolygon() doesnt support ".get_class($line));
    }

    /**
     * Clone
     * @param AbstractShape $shape
     * @return Shape2D\AbstractShape2D Clone
     */
    public static function to2D(AbstractShape $shape): Shape2D\AbstractShape2D
    {
        if($shape instanceof Shape2D\AbstractShape2D) {
            return clone $shape;
        }

        if($shape instanceof ShapeZ\AbstractShapeZ) {
            /** @var Shape2D\AbstractShape2D */
            return Shape2D3D4DFactory::createFromGeoEWKTString(
                self::_ewktConvert_3Dto2D(
                    $shape->toEWKT()
                )
            );
        }

        /** @var Shape2D\AbstractShape2D */
        return Shape2D3D4DFactory::createFromGeoEWKTString(
            self::_ewktConvert_4Dto2D(
                $shape->toEWKT()
            )
        );
    }

    /**
     * Clone
     * @param AbstractShape $shape
     * @param int|float $altitudeWhenGoingFrom2D = 0
     * @return ShapeZ\AbstractShapeZ Clone
     */
    public static function to3D(AbstractShape $shape, int|float $altitudeWhenGoingFrom2D = 0): ShapeZ\AbstractShapeZ
    {
        if($shape instanceof ShapeZ\AbstractShapeZ) {
            return clone $shape;
        }

        if($shape instanceof ShapeZM\AbstractShapeZM) {
            /** @var ShapeZ\AbstractShapeZ */
            return Shape2D3D4DFactory::createFromGeoEWKTString(
                self::_ewktConvert_4Dto3D(
                    $shape->toEWKT()
                )
            );
        }

        /** @var ShapeZ\AbstractShapeZ */
        return Shape2D3D4DFactory::createFromGeoEWKTString(
            self::_ewktConvert_2Dto3D(
                $shape->toEWKT(), $altitudeWhenGoingFrom2D
            )
        );
    }

    /**
     * Clone
     * @param AbstractShape $shape
     * @param int|float $altitudeWhenGoingFrom2D = 0
     * @param int|float $measureValueWhenGoingFrom2Dor3D = 0
     * @return ShapeZM\AbstractShapeZM Clone
     */
    public static function to4D(AbstractShape $shape, int|float $altitudeWhenGoingFrom2D = 0,  int|float $measureValueWhenGoingFrom2Dor3D = 0): ShapeZM\AbstractShapeZM
    {
        if($shape instanceof ShapeZM\AbstractShapeZM) {
            return clone $shape;
        }

        if($shape instanceof ShapeZ\AbstractShapeZ) {
            /** @var ShapeZM\AbstractShapeZM */
            return Shape2D3D4DFactory::createFromGeoEWKTString(
                self::_ewktConvert_3Dto4D(
                    $shape->toEWKT(), $measureValueWhenGoingFrom2Dor3D
                )
            );
        }

        /** @var ShapeZM\AbstractShapeZM */
        return Shape2D3D4DFactory::createFromGeoEWKTString(
            self::_ewktConvert_2Dto4D(
                $shape->toEWKT(), $altitudeWhenGoingFrom2D, $measureValueWhenGoingFrom2Dor3D
            )
        );
    }

    /**
     * @param string $ewkt
     * @return string
     */
    private static function _ewktConvert_3Dto2D(string $ewkt): string
    {
        // 1. Update the Header: Change 'ZM' to 'Z' (e.g., LINESTRING ZM -> LINESTRING Z)
        $ewkt = (string)preg_replace('/(\w+)\s*Z\b/i', '$1', $ewkt);

        // 2. Process the Coordinates
        // Matches: (X Y) Z
        // Pattern: Two numbers followed by a third.
        return (string)preg_replace_callback(
            '/([\d\.-]+\s+[\d\.-]+)\s+[\d\.-]+/',
            function ($matches) {
                // $matches[1] contains only (X, Y)
                return $matches[1];
            },
            $ewkt
        );
    }

    /**
     * @param string $ewkt
     * @return string
     */
    private static function _ewktConvert_4Dto2D(string $ewkt): string
    {
        // 1. Update the Header: Change 'ZM' to 'Z' (e.g., LINESTRING ZM -> LINESTRING Z)
        $ewkt = (string)preg_replace('/(\w+)\s*ZM/i', '$1', $ewkt);

        // 2. Process the Coordinates
        // Matches: (X Y) Z M
        // Pattern: Two numbers followed by two more numbers.
        return (string)preg_replace_callback(
            '/([\d\.-]+\s+[\d\.-]+)\s+[\d\.-]+\s+[\d\.-]+/',
            function ($matches) {
                // $matches[1] contains only (X, Y)
                return $matches[1];
            },
            $ewkt
        );
    }

    /**
     * @param string $ewkt
     * @return string
     */
    private static function _ewktConvert_4Dto3D(string $ewkt): string
    {
        // 1. Update the Header: Change 'ZM' to 'Z' (e.g., LINESTRING ZM -> LINESTRING Z)
        $ewkt = (string)preg_replace('/(\w+)\s*ZM/i', '$1 Z', $ewkt);

        // 2. Process the Coordinates
        // This regex looks for sequences of numbers/decimals separated by spaces.
        return (string)preg_replace_callback(
            '/([\d\.-]+\s+[\d\.-]+\s+[\d\.-]+)\s+[\d\.-]+/',
            function ($matches) {
                // $matches[1] contains the first three coordinates (X, Y, Z)
                // The fourth one is matched but excluded from the return
                return $matches[1];
            },
            $ewkt
        );
    }

    /**
     * @param string $ewkt
     * @param int|float $altitude
     * @return string
     */
    private static function _ewktConvert_2Dto3D(string $ewkt, int|float $altitude): string
    {
        $zValue = sprintf('%.8f', $altitude);

        // 1. Update Headers: Add 'Z' (e.g., POINT -> POINT Z)
        // We look for geometry keywords not followed by a Z/M/ZM indicator
        // This handles POINT, LINESTRING, POLYGON, and COLLECTIONs
        $ewkt = (string)preg_replace('/(POINT|LINESTRING|POLYGON|GEOMETRYCOLLECTION|MULTIPOINT|MULTILINESTRING|MULTIPOLYGON)(?!\s*[ZM])/i', '$1 Z', $ewkt);

        // 2. Add the Z coordinate
        // Matches two numbers: (X Y)
        // Replaces with: (X Y Z)
        return (string)preg_replace_callback(
            '/([\d\.-]+\s+[\d\.-]+)/',
            function ($matches) use ($zValue) {
                return $matches[1] . ' ' . $zValue;
            },
            $ewkt
        );
    }

    /**
     * @param string $ewkt
     * @param int|float $measure
     * @return string
     */
    private static function _ewktConvert_3Dto4D(string $ewkt, int|float $measure): string
    {
        $mValue = sprintf('%.8f', $measure);

        // 1. Update Headers: Change 'Z' to 'ZM' (e.g., POINT Z -> POINT ZM)
        // We use a word boundary \b to ensure we only hit the dimension flag.
        $ewkt = (string)preg_replace('/(\w+)\s*Z\b/i', '$1 ZM', $ewkt);

        // 2. Add the M coordinate
        // Matches three numbers: (X Y Z)
        // Replaces with: (X Y Z M)
        return (string)preg_replace_callback(
            '/([\d\.-]+\s+[\d\.-]+\s+[\d\.-]+)/',
            function ($matches) use ($mValue) {
                return $matches[1] . ' ' . $mValue;
            },
            $ewkt
        );
    }

    /**
     * @param string $ewkt
     * @param int|float $altitude
     * @param int|float $measure
     * @return string
     */
    private static function _ewktConvert_2Dto4D(string $ewkt, int|float $altitude, int|float $measure): string
    {
        $zVal = sprintf('%.8f', $altitude);
        $mVal = sprintf('%.8f', $measure);
        $suffix = " $zVal $mVal";

        // 1. Update Headers: Add 'ZM' (e.g., POLYGON -> POLYGON ZM)
        // Negative lookahead ensures we don't double-tag if Z/M already exists
        $geometryTypes = 'POINT|LINESTRING|POLYGON|GEOMETRYCOLLECTION|MULTIPOINT|MULTILINESTRING|MULTIPOLYGON';
        $ewkt = (string)preg_replace('/(' . $geometryTypes . ')(?!\s*[ZM])/i', '$1 ZM', $ewkt);

        // 2. Append Z and M to coordinates
        // Matches: (X Y)
        // Replaces with: (X Y Z M)
        return (string)preg_replace_callback(
            '/([\d\.-]+\s+[\d\.-]+)/',
            function ($matches) use ($suffix) {
                return $matches[1] . $suffix;
            },
            $ewkt
        );
    }

    /**
     * @param string $ewkt
     * @return array<float>
     */
    private static function _ewktConvert_3DgetZvalues(string $ewkt): array /* @phpstan-ignore-line */
    {
        // Match X Y Z groupings, capturing only the Z component
        // Handles positive/negative floats and scientific notation
        preg_match_all('/(?:[\d\.-]+(?:e[+-]?\d+)?\s+){2}([\d\.-]+(?:e[+-]?\d+)?)/i', $ewkt, $matches);

        if (empty($matches[1])) {
            return [];
        }

        // Convert string array to floats
        return array_map('floatval', $matches[1]);
    }

    /**
     * Inject an ordered array of Z-values back into a 2D EWKT string.
     *
     * @param string $ewkt
     * @param array<float|int> $zValues
     * @return string The 3D EWKT with all altitudes restored
     */
    private static function _ewktConvert_2DsetZvalues(string $ewkt, array $zValues): string /* @phpstan-ignore-line */
    {
        // 1. Update Geometry Type Headers (e.g., LINESTRING -> LINESTRING Z)
        $keywords = 'POINT|LINESTRING|POLYGON|GEOMETRYCOLLECTION|MULTIPOINT|MULTILINESTRING|MULTIPOLYGON';
        $ewkt = (string)preg_replace('/(' . $keywords . ')(?!\s*[ZM])/i', '$1 Z', $ewkt);

        // 2. Track matching indices sequentially
        $index = 0;

        // 3. Match 2D coordinate pairs and append their respective Z value
        return (string)preg_replace_callback(
            '/([\d\.-]+(?:e[+-]?\d+)?\s+[\d\.-]+(?:e[+-]?\d+)?)/i',
            function ($matches) use (&$index, $zValues) {
                // Pull the Z value or fallback to 0.0 if index out of bounds
                $z = isset($zValues[$index]) ? sprintf('%.8f', $zValues[$index]) : '0.00000000';
                $index++;

                return $matches[1] . ' ' . $z;
            },
            $ewkt
        );
    }
}