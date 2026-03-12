<?php

namespace Milanmadar\CoolioORM\Geo;

use Doctrine\DBAL\ParameterType;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiPolygon;
use Milanmadar\CoolioORM\Geo\Shape2D\Polygon;
use Milanmadar\CoolioORM\Geo\ShapeZ\AbstractShapeZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\MultiPolygonZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\PolygonZ;
use Milanmadar\CoolioORM\Geo\ShapeZM\AbstractShapeZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\MultiPolygonZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PolygonZM;
use Milanmadar\CoolioORM\Manager;

class GeoFunctions
{
    // ST_LineCrossingDirection return values
    public const NO_CROSS = 0;
    public const CROSS_LEFT = -1;
    public const CROSS_RIGHT = 1;
    public const MULTICROSS_END_LEFT = -2;
    public const MULTICROSS_END_RIGHT = 2;
    public const MULTICROSS_END_SAME_FIRST_LEFT = -3;
    public const MULTICROSS_END_SAME_FIRST_RIGHT = 3;

    private static int $parameterIndex = 0;

    /**
     * Appends '::geography' to the given expression.
     * @param string $expr
     * @return string
     */
    public static function geography(string $expr): string
    {
        return $expr.'::geography';
    }

    /**
     * Appends '::geometry' to the given expression.
     * @param string $expr
     * @return string
     */
    public static function geometry(string $expr): string
    {
        return $expr.'::geometry';
    }

    /**
     * @param AbstractShape $geom
     * @param string $topologyName
     * @param int $topologyLayerId
     * @param float $tolerance
     * @param array<mixed> $paramsWillBe
     * @param array<mixed> $paramTypesWilleBe
     * @return string
     */
    public static function toTopoGeom_param(
        AbstractShape $geom,
        string        $topologyName,
        int           $topologyLayerId,
        float         $tolerance,
        array         &$paramsWillBe,
        array         &$paramTypesWilleBe
    ): string
    {
        $geomFromEWKT = self::ST_GeomFromEWKT_param($geom, $paramsWillBe, $paramTypesWilleBe);

        $p1 = 'toTopoGeomPm' . ++self::$parameterIndex;
        $paramsWillBe[$p1] = $topologyName;
        $paramTypesWilleBe[$p1] = ParameterType::STRING;

        $p2 = 'toTopoGeomPm' . ++self::$parameterIndex;
        $paramsWillBe[$p2] = $topologyLayerId;
        $paramTypesWilleBe[$p2] = ParameterType::INTEGER;

        $p3 = 'toTopoGeomPm' . ++self::$parameterIndex;
        $paramsWillBe[$p3] = $tolerance;
        $paramTypesWilleBe[$p3] = ParameterType::STRING;

        return "toTopoGeom({$geomFromEWKT}, :{$p1}, :{$p2}, :{$p3})";
    }

    public static function toTopoGeom_geom(
        AbstractShape $geom,
        string        $topologyName,
        int           $topologyLayerId,
        float|null    $tolerance
    ): string
    {
        $geomFromEWKT = self::ST_GeomFromEWKT_geom($geom);
        return "toTopoGeom({$geomFromEWKT}, '{$topologyName}', {$topologyLayerId}, {$tolerance})";
    }

    /**
     * ```
     * toTopoGeom(
     *   ST_GeomFromEWKT('SRID=4326;MULTIPOINT(1 2)'), -- your raw geometry
     *   'topology_test_topo',                -- topology schema
     *   1,                                   -- topology layer id
     *   0.001                                -- tolerance for snapping and validation
     * )
     * ```
     * @param AbstractShape $geom
     * @param string $column
     * @param Manager $manager
     * @return string
     */
    public static function toTopoGeom_geom_mgr(
        AbstractShape $geom,
        string        $column,
        Manager       $manager
    ): string
    {
        $info = $manager->getTopoGeometryFieldInfo_column($column);
        if(!isset($info)) {
            throw new \InvalidArgumentException("GeoFunctions::toTopoGeom_geom_mgr() {$column} is not a topology column in ".get_class($manager)); // @codeCoverageIgnore
        }
        return self::toTopoGeom_geom($geom, $info['topology_name'], $info['topology_layer'], $info['tolerance']);
    }

    /**
     * @param AbstractShape $geom
     * @param array<mixed> $paramValuesWillBe
     * @param array<mixed> $paramTypesWilleBe
     * @return string
     */
    public static function ST_GeomFromEWKT_param(
        AbstractShape $geom,
        array         &$paramValuesWillBe,
        array         &$paramTypesWilleBe
    ): string
    {
        $p1 = 'GeomFromEWKTpm' . ++self::$parameterIndex;
        $paramValuesWillBe[$p1] = $geom->toEWKT();
        $paramTypesWilleBe[$p1] = ParameterType::STRING;

        return "ST_GeomFromEWKT(:{$p1})";
    }

    /**
     * ST_GeomFromEWKT('SRID=4326;POINT(1 2)')
     * @param AbstractShape $geom
     * @return string
     */
    public static function ST_GeomFromEWKT_geom(AbstractShape $geom): string
    {
        return "ST_GeomFromEWKT('{$geom->toEWKT()}')";
    }



    //
    ////// Topological Relationships
    //

    /**
     * https://postgis.net/docs/ST_3DIntersects.html
     * Note: Input MUST be a projected SRID (e.g., UTM) for results in meters.
     * ```
     * ST_3DIntersects(
     *   ST_GeomFromEWKT('SRID=32633;POINT(0 0 0)'),
     *   ST_GeomFromEWKT('SRID=32633;LINESTRING ( 0 0 0, 2 2 2)')
     * )
     * ```
     * @param AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShapeZ|AbstractShapeZM|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_3DIntersects(AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1, AbstractShapeZ|AbstractShapeZM|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_3DIntersects(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_Contains.html
     * ```
     * ST_Contains(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_Contains(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_Contains(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_ContainsProperly.html
     * ```
     * ST_ContainsProperly(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_ContainsProperly(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_ContainsProperly(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_CoveredBy.html
     * ```
     * ST_CoveredBy(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_CoveredBy(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_CoveredBy(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_Covers.html
     * ```
     * ST_Covers(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_Covers(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_Covers(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_Crosses.html
     * ```
     * ST_Crosses(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_Crosses(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_Crosses(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_Disjoint.html
     * ```
     * ST_Disjoint(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_Disjoint(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_Disjoint(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_Equals.html
     * ```
     * ST_Equals(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_Equals(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_Equals(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_Intersects.html
     * ```
     * ST_Intersects(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_Intersects(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_Intersects(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_LineCrossingDirection.html
     * ```
     * ST_LineCrossingDirection(
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * PostGIS will return GeoFunctions::CROSS_LEFT, CROSS_RIGHT, MULTICROSS_END_LEFT, MULTICROSS_END_RIGHT, MULTICROSS_END_SAME_FIRST_LEFT, MULTICROSS_END_SAME_FIRST_RIGHT
     * @param LineString|LineStringZ|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param LineString|LineStringZ|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string PostGIS will return GeoFunctions::CROSS_LEFT, CROSS_RIGHT, MULTICROSS_END_LEFT, MULTICROSS_END_RIGHT, MULTICROSS_END_SAME_FIRST_LEFT, MULTICROSS_END_SAME_FIRST_RIGHT
     */
    public static function ST_LineCrossingDirection(LineString|LineStringZ|string $geomOrExpr1, LineString|LineStringZ|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_LineCrossingDirection(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_OrderingEquals.html
     * ```
     * ST_OrderingEquals(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_OrderingEquals(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_OrderingEquals(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_Overlaps.html
     * ```
     * ST_Overlaps(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_Overlaps(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_Overlaps(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_Relate.html
     * ```
     * ST_Relate(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param string|int|null $intersectionMatrixPattern_or_boundaryNodeRule Optional
     * @return string
     */
    public static function ST_Relate(
        AbstractShape|string $geomOrExpr1,
        AbstractShape|string $geomOrExpr2,
        string|int|null $intersectionMatrixPattern_or_boundaryNodeRule = null
    ): string
    {
        if(isset($intersectionMatrixPattern_or_boundaryNodeRule)) {
            return sprintf(
                "ST_Relate(%s, %s, %s)",
                is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
                is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2),
                $intersectionMatrixPattern_or_boundaryNodeRule
            );
        }
        return sprintf(
            "ST_Relate(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_RelateMatch.html
     * ```
     * ST_RelateMatch('101202FFF', 'TTTTTTFFF')
     * ```
     * @param string $intersectionMatrix
     * @param string $intersectionMatrixPattern
     * @return string
     */
    public static function ST_RelateMatch(string $intersectionMatrix, string $intersectionMatrixPattern): string
    {
        return sprintf(
            "ST_RelateMatch(%s, %s)",
            $intersectionMatrix,
            $intersectionMatrixPattern
        );
    }

    /**
     * https://postgis.net/docs/ST_Touches.html
     * ```
     * ST_Touches(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_Touches(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_Touches(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_Within.html
     * ```
     * ST_Within(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_Within(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_Within(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }



    //
    /////// Distance Relationships
    //

    /**
     * https://postgis.net/docs/ST_3DDWithin.html
     * Note: Input MUST be a projected SRID (e.g., UTM) for results in meters.
     * ```
     * ST_3DDWithin(
     *   ST_GeomFromEWKT('SRID=32633;POINT(0 0 0)'),
     *   ST_GeomFromEWKT('SRID=32633;LINESTRING (0 0 0, 2 2 2)'),
     *   2.5
     * )
     * ```
     * @param AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShapeZ|AbstractShapeZM|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param float|int $distance
     * @return string
     */
    public static function ST_3DDWithin(AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1, AbstractShapeZ|AbstractShapeZM|string $geomOrExpr2, float|int $distance): string
    {
        return sprintf(
            "ST_3DDWithin(%s, %s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2),
            $distance
        );
    }

    /**
     * https://postgis.net/docs/ST_3DDFullyWithin.html
     * Note: Input MUST be a projected SRID (e.g., UTM) for results in meters.
     * ```
     * ST_3DDFullyWithin(
     *   ST_GeomFromEWKT('SRID=32633;POINT Z(0 0 0)'),
     *   ST_GeomFromEWKT('SRID=32633;LINESTRING Z(0 0 0, 2 2 2)'),
     *   2.5
     * )
     * ```
     * @param AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShapeZ|AbstractShapeZM|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param float|int $distance
     * @return string
     */
    public static function ST_3DDFullyWithin(AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1, AbstractShapeZ|AbstractShapeZM|string $geomOrExpr2, float|int $distance): string
    {
        return sprintf(
            "ST_3DDFullyWithin(%s, %s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2),
            $distance
        );
    }

    /**
     * https://postgis.net/docs/ST_DFullyWithin.html
     * ```
     * ST_DFullyWithin(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )'),
     *   2.5
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param float|int $distance
     * @return string
     */
    public static function ST_DFullyWithin(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2, float|int $distance): string
    {
        return sprintf(
            "ST_DFullyWithin(%s, %s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2),
            $distance
        );
    }

    /**
     * https://postgis.net/docs/ST_DWithin.html
     * ```
     * ST_DWithin(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )'),
     *   2.5
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param float|int $distance
     * @return string
     */
    public static function ST_DWithin(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2, float|int $distance): string
    {
        return sprintf(
            "ST_DWithin(%s, %s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2),
            $distance
        );
    }



    //
    ////// Measurement Functions
    //

    /**
     * https://postgis.net/docs/ST_Distance.html
     * ```
     * ST_Distance(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )'),
     *   2.5
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param bool $use_spheroid Optional. Default is true
     * @return string
     */
    public static function ST_Distance(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2, bool $use_spheroid = true): string
    {
        return sprintf(
            "ST_Distance(%s, %s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2),
            $use_spheroid ? 'true' : 'false'
        );
    }

    /**
     * https://postgis.net/docs/ST_Distance.html
     * Note: Input MUST be a projected SRID (e.g., UTM) for results in meters.
     * ```
     * ST_3DDistance(
     *   ST_GeomFromEWKT('SRID=32633;POINT Z(0 0 0)'),
     *   ST_GeomFromEWKT('SRID=32633;LINESTRING Z(0 0 0, 2 2 2)')
     * )
     * ```
     * @param AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShapeZ|AbstractShapeZM|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_3DDistance(AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1, AbstractShapeZ|AbstractShapeZM|string $geomOrExpr2): string
    {
        return sprintf(
            "ST_3DDistance(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2)
        );
    }

    /**
     * https://postgis.net/docs/ST_Length.html
     * ```
     * ST_Length(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   false
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param bool $use_spheroid Optional. Default is true
     * @return string
     */
    public static function ST_Length(AbstractShape|string $geomOrExpr1, bool $use_spheroid = true): string
    {
        if($geomOrExpr1 instanceof Polygon || $geomOrExpr1 instanceof MultiPolygon) {
            //throw new \InvalidArgumentException('SGeoFunctions::ST_Length() does not support Polygon or MultiPolygon. Use ST_Perimeter for Polygons'); // @codeCoverageIgnore
            return self::ST_Perimeter($geomOrExpr1, $use_spheroid);
        }
        return sprintf(
            "ST_Length(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            $use_spheroid ? 'true' : 'false'
        );
    }

    /**
     * https://postgis.net/docs/ST_3DLength.html
     * Calculates the 3D length of a linear geometry (LineString, MultiLineString).
     * Note: Input MUST be a projected SRID (e.g., UTM) for results in meters.
     * Example output: ST_3DLength(ST_GeomFromEWKT('SRID=32633;LINESTRING ZM(0 0 0 0, 1 1 1 1)'))
     * @param AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1 Geometry Object OR column name OR ST_ function output
     * @return string
     */
    public static function ST_3DLength(AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1): string
    {
        if ($geomOrExpr1 instanceof PolygonZ  || $geomOrExpr1 instanceof MultiPolygonZ
        ||  $geomOrExpr1 instanceof PolygonZM || $geomOrExpr1 instanceof MultiPolygonZM) {
            // PostGIS 3D version of perimeter is still usually handled by ST_3DLength
            // on the exterior ring, but for a general library, consistency is better.
            //throw new \InvalidArgumentException('ST_3DLength() does not support Polygon/MultiPolygon. Use ST_3DPerimeter.');
            return self::ST_3DPerimeter($geomOrExpr1);
        }

        return sprintf(
            "ST_3DLength(%s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1)
        );
    }

    /**
     * https://postgis.net/docs/ST_IsValid.html
     * Returns true if the geometry is well-formed in 2D.
     * ALSO CHECK ST_IsValidReason to know why ST_IsValid failed
     *
     * @param AbstractShape|string $geomOrExpr
     * @return string
     */
    public static function ST_IsValid(AbstractShape|string $geomOrExpr): string
    {
        return sprintf(
            "ST_IsValid(%s)",
            is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr)
        );
    }

    /**
     * https://postgis.net/docs/ST_IsValidReason.html
     * Returns a text description of why a geometry is invalid.
     * USE CASE: If ST_IsValid returns false, use this to see the reason
     *
     * @param AbstractShape|string $geomOrExpr
     * @return string
     */
    public static function ST_IsValidReason(AbstractShape|string $geomOrExpr): string
    {
        return sprintf(
            "ST_IsValidReason(%s)",
            is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr)
        );
    }

    /**
     * https://postgis.net/docs/ST_ShortestLine.html
     * ```
     * ST_ShortestLine(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )'),
     *   false
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param bool $use_spheroid Optional. Default is true
     * @return string EWKB format
     */
    public static function ST_ShortestLine(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2, bool $use_spheroid = true): string
    {
        return sprintf(
            "ST_ShortestLine(%s, %s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            is_string($geomOrExpr2) ? $geomOrExpr2 : self::ST_GeomFromEWKT_geom($geomOrExpr2),
            $use_spheroid ? 'true' : 'false'
        );
    }

    /**
     * https://postgis.net/docs/ST_ShortestLine.html
     * ```
     * ST_ShortestLine(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )'),
     *   2.5
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param bool $use_spheroid Optional. Default is true
     * @return string EWKT format
     */
    public static function ST_ShortestLine_asEWKT(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2, bool $use_spheroid = true): string
    {
        return sprintf(
            "ST_asEWKT(%s)",
            self::ST_ShortestLine($geomOrExpr1, $geomOrExpr2, $use_spheroid)
        );
    }

    /**
     * https://postgis.net/docs/ST_Perimeter.html
     * ```
     * ST_Perimeter(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   false
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param bool $use_spheroid Optional. Default is true
     * @return string
     */
    public static function ST_Perimeter(AbstractShape|string $geomOrExpr1, bool $use_spheroid = true): string
    {
        return sprintf(
            "ST_Perimeter(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            $use_spheroid ? 'true' : 'false'
        );
    }

    /**
     * Note: Input MUST be a projected SRID (e.g., UTM) for results in meters.
     * Example output: ST_3DPerimeter(ST_GeomFromEWKT('SRID=32633;LINESTRING ZM(0 0 0 0, 1 1 1 1)'))
     * @param AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1
     * @return string
     */
    public static function ST_3DPerimeter(AbstractShapeZ|AbstractShapeZM|string $geomOrExpr1): string
    {
        return sprintf(
            "ST_3DPerimeter(%s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1)
        );
    }

    /**
     * https://postgis.net/docs/ST_ZMax.html
     * Returns the maximum Z coordinate of a geometry.
     * * @param AbstractShape|string $geomOrExpr
     * @return string
     */
    public static function ST_ZMax(AbstractShape|string $geomOrExpr): string
    {
        $geom = is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr);
        return sprintf("ST_ZMax(%s)", $geom);
    }

    /**
     * https://postgis.net/docs/ST_ZMin.html
     * Returns the minimum Z coordinate of a geometry.
     * * @param AbstractShape|string $geomOrExpr
     * @return string
     */
    public static function ST_ZMin(AbstractShape|string $geomOrExpr): string
    {
        $geom = is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr);
        return sprintf("ST_ZMin(%s)", $geom);
    }

    /**
     * https://postgis.net/docs/ST_Simplify.html
     * Returns a "simplified" version of the given geometry using the Douglas-Peucker algorithm.
     * ALSO CHECK ST_SimplifyPreserveTopology()
     *
     * @param AbstractShape|string $geomOrExpr Geometry Object or Column
     * @param float $tolerance The distance tolerance (in SRID units, e.g., meters). Points within this distance of the simplified line are removed.
     * @param bool $preserveBoundary If true, prevents simplification of endpoints.
     * @return string
     */
    public static function ST_Simplify(
        AbstractShape|string $geomOrExpr,
        float $tolerance,
        bool $preserveBoundary = false
    ): string {
        return sprintf(
            "ST_Simplify(%s, %f, %s)",
            is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr),
            $tolerance,
            $preserveBoundary ? 'true' : 'false'
        );
    }

    /**
     * https://postgis.net/docs/ST_SimplifyPreserveTopology.html
     * Returns a "simplified" version of the given geometry, ensuring that the simplification does not create invalid geometries (like self-intersections).
     * USE CASE: Use this for high-density GPS tracks where switchbacks or tight loops are common, to avoid breaking your routing topology.
     *
     * @param AbstractShape|string $geomOrExpr Geometry Object or Column
     * @param float $tolerance The distance tolerance in SRID units (e.g., meters).
     * @return string
     */
    public static function ST_SimplifyPreserveTopology(AbstractShape|string $geomOrExpr, float $tolerance): string
    {
        return sprintf(
            "ST_SimplifyPreserveTopology(%s, %f)",
            is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr),
            $tolerance
        );
    }

    /**
     * https://postgis.net/docs/ST_SnapToGrid.html
     * Snaps all points of the input geometry to a regular grid.
     * @param AbstractShape|string $geomOrExpr
     * @param float $sizeX  Grid size for X. If only this is provided, Y follows X.
     * @param float|null $sizeY Optional. Grid size for Y.
     * @param float|null $sizeZ Optional. Grid size for Z (Elevation).
     * @param float|null $sizeM Optional. Grid size for M (Measure/Time). If you leave it out, internally it will use 0 which means (leave this out from snapping jusst preserve it)
     * @return string
     */
    public static function ST_SnapToGrid(
        AbstractShape|string $geomOrExpr,
        float $sizeX,
        ?float $sizeY = null,
        ?float $sizeZ = null,
        ?float $sizeM = null
    ): string {
        $geom = is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr);

        // Logic to handle PostGIS overloading
        if ($sizeM !== null) {
            return sprintf("ST_SnapToGrid(%s, %f, %f, %f, %f)", $geom, $sizeX, $sizeY ?? $sizeX, $sizeZ ?? 0, $sizeM);
        }
        if ($sizeZ !== null) {
            return sprintf("ST_SnapToGrid(%s, %f, %f, %f, 0)", $geom, $sizeX, $sizeY ?? $sizeX, $sizeZ);
        }
        if ($sizeY !== null) {
            return sprintf("ST_SnapToGrid(%s, %f, %f)", $geom, $sizeX, $sizeY);
        }

        return sprintf("ST_SnapToGrid(%s, %f)", $geom, $sizeX);
    }

    /**
     * https://postgis.net/docs/ST_Snap.html
     * Snaps the vertices and segments of the input geometry to the target geometry.
     *
     * @param AbstractShape|string $inputGeom The geometry you want to move.
     * @param AbstractShape|string $targetGeom The "anchor" geometry to snap to.
     * @param float $tolerance The snapping distance (in SRID units, e.g., meters).
     * @return string
     */
    public static function ST_Snap(
        AbstractShape|string $inputGeom,
        AbstractShape|string $targetGeom,
        float $tolerance
    ): string {
        return sprintf(
            "ST_Snap(%s, %s, %f)",
            is_string($inputGeom) ? $inputGeom : self::ST_GeomFromEWKT_geom($inputGeom),
            is_string($targetGeom) ? $targetGeom : self::ST_GeomFromEWKT_geom($targetGeom),
            $tolerance
        );
    }

    /**
     * https://postgis.net/docs/ST_LineMerge.html
     * Merges a collection of linear geometries into a single LineString.
     * @param AbstractShape|string $geomOrExpr A MultiLineString or GeometryCollection
     * @param bool $directed Optional (PostGIS 3.1+). If true, only merges lines if their start/end points match in the correct direction.
     * @return string
     */
    public static function ST_LineMerge(AbstractShape|string $geomOrExpr, bool $directed = false): string
    {
        $geom = is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr);

        // The 'directed' parameter was added in PostGIS 3.1
        return sprintf("ST_LineMerge(%s, %s)", $geom, $directed ? 'true' : 'false');
    }

    /**
     * https://postgis.net/docs/ST_Force3DZ.html
     * Forces the geometry into XYZ (3D) format.
     *  - If 2D (XY), Z becomes 0.
     *  - If 4D (XYZM), M is discarded.
     */
    public static function ST_Force3DZ(AbstractShape|string $geomOrExpr): string
    {
        return sprintf("ST_Force3DZ(%s)",
            is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr)
        );
    }

    /**
     * https://postgis.net/docs/ST_Force4D.html
     * Forces the geometry into XYZM (4D) format.
     * USE CASE: Use this on your 'Z' network to add a dummy 'M'
     * so it can be merged/unioned with ZM hiker tracks.
     */
    public static function ST_Force4D(AbstractShape|string $geomOrExpr): string
    {
        return sprintf("ST_Force4D(%s)",
            is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr)
        );
    }

    /**
     * https://postgis.net/docs/ST_Transform.html
     * Returns a new geometry with its coordinates transformed to a different SRID.
     * @param AbstractShape|string $geomOrExpr The geometry to transform.
     * @param int $targetSrid The SRID of the coordinate system to transform to (e.g., 4326 or 32633).
     * @return string
     */
    public static function ST_Transform(AbstractShape|string $geomOrExpr, int $targetSrid): string
    {
        $geom = is_string($geomOrExpr) ? $geomOrExpr : self::ST_GeomFromEWKT_geom($geomOrExpr);

        return sprintf("ST_Transform(%s, %d)", $geom, $targetSrid);
    }

    /**
     * https://postgis.net/docs/ST_Buffer.html
     * ```
     * ST_Buffer(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   2,
     *   'join=mitre mitre_limit=5.0'
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param float|int|string $radiusOrExpr2 radius OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param string|null $options Optional. See official docs
     * @return string
     */
    public static function ST_Buffer(AbstractShape|string $geomOrExpr1, float|int|string $radiusOrExpr2, string|null $options = null): string
    {
        if(isset($options)) {
            return sprintf(
                "ST_Buffer(%s, %s, %s)",
                is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
                $radiusOrExpr2,
                $options
            );
        }
        return sprintf(
            "ST_Buffer(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            $radiusOrExpr2
        );
    }

}