<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

use Milanmadar\CoolioORM\Geo\Shape2D3D4DFactory;

class GeometryCollectionZM extends AbstractShapeZM
{
    /** @var AbstractShapeZM[] */
    private array $geometries;

    /**
     * @param AbstractShapeZM[] $geometries
     * @param int|null $srid
     */
    public function __construct(array $geometries, int|null $srid = null)
    {
        if (empty($geometries)) {
            throw new \InvalidArgumentException('GeometryCollectionZM must have at least one geometry.');
        }

        if(!isset($srid)) $srid = $geometries[0]->getSrid();
        parent::__construct($srid);
        $this->geometries = $geometries;
    }

    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): GeometryCollectionZM
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (!isset($jsonData['type'], $jsonData['geometries']) || $jsonData['type'] !== 'GeometryCollection') {
            throw new \InvalidArgumentException('Invalid GeoJSON for GeometryCollectionZM.');
        }

        $geometries = [];
        foreach ($jsonData['geometries'] as $geometryData) {
            /** @var AbstractShapeZM $_ */
            $_ = Shape2D3D4DFactory::createFromGeoJSON($geometryData, $srid);
            $geometries[] = $_;
        }

        return new GeometryCollectionZM($geometries, $srid);
    }

    public static function createFromGeoEWKTString(string $ewktString): GeometryCollectionZM
    {
        if (strpos($ewktString, 'GEOMETRYCOLLECTION') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected GEOMETRYCOLLECTION ZM type.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) !== 2) {
            throw new \InvalidArgumentException('Invalid EWKT string: missing SRID or geometry.');
        }

        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);

        // Extract geometries while respecting parentheses
        preg_match('/GEOMETRYCOLLECTION ?Z?M?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid GEOMETRYCOLLECTIONZM format in EWKT.');
        }

        $geomString = $matches[1];
        $geometries = [];
        $parenCount = 0;
        $current = '';

        for ($i = 0; $i < strlen($geomString); $i++) {
            $c = $geomString[$i];
            if ($c === '(') $parenCount++;
            elseif ($c === ')') $parenCount--;

            if ($parenCount === 0 && $c === ',') {
                /** @var AbstractShapeZM $_ */
                $_ = Shape2D3D4DFactory::createFromGeoEWKTString("SRID=$srid;$current");
                $geometries[] = $_;
                $current = '';
            } else {
                $current .= $c;
            }
        }

        if (!empty(trim($current))) {
            /** @var AbstractShapeZM $_ */
            $_ = Shape2D3D4DFactory::createFromGeoEWKTString("SRID=$srid;$current");
            $geometries[] = $_;
        }

        return new GeometryCollectionZM($geometries, $srid);
    }

    /**
     * @return AbstractShapeZM[]
     */
    public function getGeometries(): array
    {
        return $this->geometries;
    }

    /**
     * Convert to WKT ZM
     */
    public function toWKT(): string
    {
        $wktParts = array_map(fn(AbstractShapeZM $g) => $g->toWKT(), $this->geometries);
        return 'GEOMETRYCOLLECTION ZM(' . implode(',', $wktParts) . ')';
    }

    /**
     * Convert to GeoJSON
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'GeometryCollection',
            'geometries' => array_map(fn(AbstractShapeZM $g) => $g->toGeoJSON(), $this->geometries),
        ];
    }
}