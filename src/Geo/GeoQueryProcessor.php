<?php

namespace Milanmadar\CoolioORM\Geo;

use Milanmadar\CoolioORM\Manager;

class GeoQueryProcessor
{
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

            $_cols = [];
            $fieldTypes = $mgr->getFieldTypes();
            foreach($columns as $c) {
                if(isset($fieldTypes[$c])) {
                    if($fieldTypes[$c] == 'geometry') {
                        $_cols[] = "ST_AsGeoJSON({$c}) AS {$c}";
                        $_cols[] = "ST_SRID({$c}) AS {$c}_srid";
                    } elseif($fieldTypes[$c] == 'geometry_curved') {
                        $_cols[] = "ST_AsEWKT({$c}) as {$c}";
                    } else {
                        $_cols[] = $c;
                    }
                } else {
                    $_cols[] = $c;
                }
            }

            $processedSelect = implode(", ", array_map(fn($c) => $c, $_cols));

            // Replace the original SELECT part with the processed one
            /** @var string $sql */
            $sql = preg_replace('/^\s*SELECT\s+(.*)\s+FROM\s+/i', 'SELECT ' . $processedSelect . ' FROM ', $sql);
        }

        return $sql;
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