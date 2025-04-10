<?php

namespace Milanmadar\CoolioORM\Geo;

use Doctrine\DBAL\ParameterType;

class GeoFunctions
{
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
        $paramsWillBe[] = $topologyName;
        $paramTypesWilleBe[] = ParameterType::STRING;
        $paramsWillBe[] = $topologyLayerId;
        $paramTypesWilleBe[] = ParameterType::INTEGER;
        $paramsWillBe[] = $tolerance;
        $paramTypesWilleBe[] = ParameterType::STRING;
        return "toTopoGeom({$geomFromEWKT}, ?, ?, ?)";
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
        $paramValuesWillBe[] = $shape->toEWKT();
        $paramTypesWilleBe[] = ParameterType::STRING;
        return 'ST_GeomFromEWKT(?)';
    }

    public static function ST_AsGeoJSON(string $geometry): string
    {
        return sprintf('ST_AsGeoJSON(%s)', $geometry);
    }

    public static function ST_AsText(string $geometry): string
    {
        return sprintf('ST_AsText(%s)', $geometry);
    }
}