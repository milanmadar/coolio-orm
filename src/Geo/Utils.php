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
     * @return float|int
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
}