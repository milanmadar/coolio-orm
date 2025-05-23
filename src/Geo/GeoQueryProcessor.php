<?php

namespace Milanmadar\CoolioORM\Geo;

use Milanmadar\CoolioORM\Manager;

class GeoQueryProcessor
{
    /**
     * @param Manager|null $mgr
     * @param string $column
     * @param AbstractShape $shape
     * @return array{ string, array<string, mixed>, array<string, mixed> } SQLpart, paramValues, paramTypes
     */
    public static function geoFunction_sqlPart_andParams(Manager|null $mgr, string $column, AbstractShape $shape): array
    {
        $paramValues = [];
        $paramTypes = [];

        // topogeometry
        $topoGeomFieldInfo_column = $mgr?->getTopoGeometryFieldInfo_column($column);
        if(isset($topoGeomFieldInfo_column)) {
            $sqlPart = GeoFunctions::toTopoGeom_param(
                $shape,
                $topoGeomFieldInfo_column['topology_name'],
                $topoGeomFieldInfo_column['topology_layer'],
                $topoGeomFieldInfo_column['tolerance'],
                $paramValues,
                $paramTypes
            );
        }
        else // regular geometry
        {
            $sqlPart = GeoFunctions::ST_GeomFromEWKT_param(
                $shape,
                $paramValues,
                $paramTypes
            );
        }

        return [$sqlPart, $paramValues, $paramTypes];
    }

    public static function processQuery(string $sql, Manager $mgr): string
    {
        // extract the SELECT part
        if (preg_match('/^\s*SELECT\s+(.*)\s+FROM\s+/i', $sql, $matches))
        {
            $selectPart = $matches[1];

            $selectPart = trim($selectPart);

            // Get each projection (each column)
            $columns = self::splitSelectColumns($selectPart);

            if($columns[0] == '*') {
                $columns = $mgr->getFields();
            }

            $_cols = self::SELECTgeometryToPostGISformat($mgr->getFieldTypes(), $columns);

            $processedSelect = implode(", ", array_map(fn($c) => $c, $_cols));

            // Replace the original SELECT part with the processed one
            /** @var string $sql */
            $sql = preg_replace('/^\s*SELECT\s+(.*)\s+FROM\s+/i', 'SELECT ' . $processedSelect . ' FROM ', $sql);
        }

        return $sql;
    }

    /**
     * Converts geometry fields to PostGIS format for a SELECT SQL queries. Leaves other fields as they were
     *
     * @param array<string, string> $managerFieldTypes $manager->getFieldTypes()
     * @param array<string> $fieldsInTheSQLstring
     * @return array<string> The formatted fields, geometries transformed to 'ST_As...` for PostGIS.
     */
    public static function SELECTgeometryToPostGISformat(array $managerFieldTypes, array $fieldsInTheSQLstring): array
    {
        $cols = [];
        foreach($fieldsInTheSQLstring as $c) {
            if(isset($managerFieldTypes[$c])) {
                /*if($managerFieldTypes[$c] == 'geometry') {
                    $cols[] = "ST_AsGeoJSON({$c}) AS {$c}";
                    $cols[] = "ST_SRID({$c}) AS {$c}_srid";
                } elseif($managerFieldTypes[$c] == 'geometry_curved' || $managerFieldTypes[$c] == 'topogeometry') {
                    $cols[] = "ST_AsEWKT({$c}) AS {$c}";
                } else {
                    $cols[] = $c;
                }*/
                if($managerFieldTypes[$c] == 'geometry' || $managerFieldTypes[$c] == 'geometry_curved' || $managerFieldTypes[$c] == 'topogeometry') {
                    $cols[] = "ST_AsEWKT({$c}) AS {$c}";
                } else {
                    $cols[] = $c;
                }
            } else {
                $cols[] = $c;
            }
        }
        return $cols;
    }

    /**
     * @param string $selectPart
     * @return array<string>
     */
    private static function splitSelectColumns(string $selectPart): array
    {
        $columns = [];
        $depth = 0; // Keeps track of parentheses depth
        $start = 0; // Start position of each column

        // Loop through the SELECT part character by character
        for ($i = 0; $i < strlen($selectPart); $i++) {
            $char = $selectPart[$i];

            // Handle parentheses to avoid splitting inside functions like COALESCE, LENGTH, etc.
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                // Split at commas that are outside of parentheses
                $columns[] = trim(substr($selectPart, $start, $i - $start));
                $start = $i + 1;
            }
        }

        // Add the last column after the final comma or end of the string
        $columns[] = trim(substr($selectPart, $start));

        return $columns;
    }
}