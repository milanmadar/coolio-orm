<?php

namespace Milanmadar\CoolioORM\Geo;

use Doctrine\DBAL\ParameterType;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiPolygon;
use Milanmadar\CoolioORM\Geo\Shape2D\Polygon;
use Milanmadar\CoolioORM\Geo\ShapeZ\AbstractShapeZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
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
     * ```
     * ST_3DIntersects(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0 0, 2 2 2)')
     * )
     * ```
     * @param AbstractShapeZ|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShapeZ|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_3DIntersects(AbstractShapeZ|string $geomOrExpr1, AbstractShapeZ|string $geomOrExpr2): string
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
     * ```
     * ST_3DDWithin(
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
    public static function ST_3DDWithin(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2, float|int $distance): string
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
     * ```
     * ST_3DDFullyWithin(
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
    public static function ST_3DDFullyWithin(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2, float|int $distance): string
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
     * ```
     * ST_3DDistance(
     *   ST_GeomFromEWKT('SRID=4326;POINT(0 0)'),
     *   ST_GeomFromEWKT('SRID=4326;LINESTRING ( 0 0, 2 2 )')
     * )
     * ```
     * @param AbstractShape|string $geomOrExpr1 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @param AbstractShape|string $geomOrExpr2 Geometry Object (CoolioORM\Geo\Shape) OR column name OR the output of another ST_ function OR an expression (string are not safe from query injection)
     * @return string
     */
    public static function ST_3DDistance(AbstractShape|string $geomOrExpr1, AbstractShape|string $geomOrExpr2): string
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
            throw new \InvalidArgumentException('SGeoFunctions::ST_Length() does not support Polygon or MultiPolygon. Use ST_Perimeter for Polygons'); // @codeCoverageIgnore
        }
        return sprintf(
            "ST_Length(%s, %s)",
            is_string($geomOrExpr1) ? $geomOrExpr1 : self::ST_GeomFromEWKT_geom($geomOrExpr1),
            $use_spheroid ? 'true' : 'false'
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



    //
    ////// Geometry Processing
    //

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