<?php

namespace Milanmadar\CoolioORM\Geo;

use Doctrine\DBAL\ParameterType;

class GeoFunctions
{
    private static $parameterIndex = 0;

    /**
     * @param AbstractShape $shape
     * @param string $topologyName
     * @param int $topologyLayerId
     * @param float $tolerance
     * @param array<mixed> $paramsWillBe
     * @param array<mixed> $paramTypesWilleBe
     * @return string
     */
    public static function toTopoGeom_param(
        AbstractShape $shape,
        string $topologyName,
        int $topologyLayerId,
        float $tolerance,
        array &$paramsWillBe,
        array &$paramTypesWilleBe
    ): string
    {
        $geomFromEWKT = self::ST_GeomFromEWKT_param($shape, $paramsWillBe, $paramTypesWilleBe);

        $p1 = 'toTopoGeom_p' . ++self::$parameterIndex;
        $paramsWillBe[$p1] = $topologyName;
        $paramTypesWilleBe[$p1] = ParameterType::STRING;

        $p2 = 'toTopoGeom_p' . ++self::$parameterIndex;
        $paramsWillBe[$p2] = $topologyLayerId;
        $paramTypesWilleBe[$p2] = ParameterType::INTEGER;

        $p3 = 'toTopoGeom_p' . ++self::$parameterIndex;
        $paramsWillBe[$p3] = $tolerance;
        $paramTypesWilleBe[$p3] = ParameterType::STRING;

        return "toTopoGeom({$geomFromEWKT}, :{$p1}, :{$p2}, :{$p3})";
    }

    /**
     * @param AbstractShape $shape
     * @param array<mixed> $paramValuesWillBe
     * @param array<mixed> $paramTypesWilleBe
     * @return string
     */
    public static function ST_GeomFromEWKT_param(
        AbstractShape $shape,
        array &$paramValuesWillBe,
        array &$paramTypesWilleBe
    ): string
    {
        $p1 = 'GeomFromEWKT_p' . ++self::$parameterIndex;
        $paramValuesWillBe[$p1] = $shape->toEWKT();
        $paramTypesWilleBe[$p1] = ParameterType::STRING;

        return "ST_GeomFromEWKT(:{$p1})";
    }
}