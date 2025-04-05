<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class MultiCurve extends Geometry
{

    /** @var array<LineString|CircularString|CompoundCurve> */
    private array $curves;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return MultiCurve
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): MultiCurve
    {
        // GeoJSON does not support CircularString by spec
        throw new \RuntimeException('GeoJSON does not support MultiCurve. Use EWKT instead.');
    }

    /**
     * @param string $ewktString
     * @return MultiCurve
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiCurve
    {
        // Extract the SRID and geometry part from the EWKT string.
        if (strpos($ewktString, 'MULTICURVE') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for MultiCurve.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);
        preg_match('/MULTICURVE\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid MultiCurve format in EWKT.');
        }

        $curves = [];
        $geometryPart = $matches[1];

        // Use regex to extract each curve safely (supports CIRCULARSTRING, COMPOUNDCURVE, or untyped LINESTRING)
        preg_match_all('/(CIRCULARSTRING\([^)]*\)|COMPOUNDCURVE\([^)]*\)|\([^\)]+\))/i', $geometryPart, $curveMatches);

        if (empty($curveMatches[0])) {
            throw new \InvalidArgumentException('Invalid curves in MultiCurve.');
        }

        foreach ($curveMatches[0] as $curveString) {
            $curveString = trim($curveString);

            if (stripos($curveString, 'CIRCULARSTRING') === 0) {
                $curves[] = CircularString::createFromGeoEWKTString("SRID=$srid;$curveString");
            } elseif (stripos($curveString, 'COMPOUNDCURVE') === 0) {
                $curves[] = CompoundCurve::createFromGeoEWKTString("SRID=$srid;$curveString");
            } elseif (stripos($curveString, 'LINESTRING') === 0) {
                $curves[] = LineString::createFromGeoEWKTString("SRID=$srid;$curveString");
            } else {
                // It's an untyped LINESTRING (just points in parentheses)
                $curves[] = LineString::createFromGeoEWKTString("SRID=$srid;LINESTRING$curveString");
            }
        }

        return new MultiCurve($curves, $srid);
    }


    /**
     * @param array<LineString|CircularString|CompoundCurve> $curves An array of LineString, CircularString, or CompoundCurve objects.
     * @param int|null $srid The SRID of the MultiCurve.
     */
    public function __construct(array $curves, int|null $srid = null)
    {
        parent::__construct($srid);
        $this->curves = $curves;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $curveWKT = array_map(fn($curve) => $curve->toWKT(), $this->curves);
        return 'MULTICURVE(' . implode(',', $curveWKT) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        $curveGeoJSON = array_map(fn($curve) => $curve->toGeoJSON(), $this->curves);
        return [
            'type' => 'MultiCurve',
            'coordinates' => $curveGeoJSON
        ];
    }

    /**
     * @return array<LineString|CircularString|CompoundCurve>
     */
    public function getCurves(): array
    {
        return $this->curves;
    }
}
