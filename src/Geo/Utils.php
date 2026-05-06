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
     * @param AbstractShape $geom1
     * @param AbstractShape $geom2
     * @param Manager|Connection $dbOrMgr
     * @param int $roundToPrecision optional
     * @return float
     */
    public static function getDistanceInMeters(AbstractShape $geom1, AbstractShape $geom2, Manager|Connection $dbOrMgr, int $roundToPrecision = -1): float
    {
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
            return clone $returnPointOnThisGeom;
        }
        // multipooint
        elseif($returnPointOnThisGeom instanceof Shape2D\MultiPoint
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