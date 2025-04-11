<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class MultiCurveZ extends AbstractShapeZ
{
    /** @var array<LineStringZ|CircularStringZ|CompoundCurveZ> */
    private array $curves;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return MultiCurveZ
     */
    /*public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): MultiCurveZ
    {
        // GeoJSON does not support CircularString or Z coordinates natively.
        throw new \RuntimeException('GeoJSON does not support MultiCurveZ. Use EWKT instead.');
    }*/

    /**
     * @param string $ewktString
     * @return MultiCurveZ
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiCurveZ
    {
        if (strpos($ewktString, 'MULTICURVE') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for MultiCurveZ.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);
        preg_match('/MULTICURVE ?Z?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid MultiCurveZ format in EWKT.');
        }

        $geometryPart = $matches[1];

        // Capture each curve inside MultiCurveZ
        preg_match_all('/(CIRCULARSTRING ?Z?\([^)]*\)|COMPOUNDCURVE ?Z?\([^)]*\)|LINESTRING ?Z?\([^)]*\)|\([^\)]+\))/i', $geometryPart, $curveMatches);

        if (empty($curveMatches[0])) {
            throw new \InvalidArgumentException('Invalid curves in MultiCurveZ.');
        }

        $curves = [];
        foreach ($curveMatches[0] as $curveString) {
            $curveString = trim($curveString);

            if (stripos($curveString, 'CIRCULARSTRING') === 0) {
                $curves[] = CircularStringZ::createFromGeoEWKTString("SRID=$srid;$curveString");
            } elseif (stripos($curveString, 'COMPOUNDCURVE') === 0) {
                $curves[] = CompoundCurveZ::createFromGeoEWKTString("SRID=$srid;$curveString");
            } elseif (stripos($curveString, 'LINESTRING') === 0) {
                $curves[] = LineStringZ::createFromGeoEWKTString("SRID=$srid;$curveString");
            } else {
                // Untyped = assume it's a 3D LineString
                $curves[] = LineStringZ::createFromGeoEWKTString("SRID=$srid;LINESTRINGZ$curveString");
            }
        }

        return new MultiCurveZ($curves, $srid);
    }

    /**
     * @param array<LineStringZ|CircularStringZ|CompoundCurveZ> $curves
     * @param int|null $srid
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
        return 'MULTICURVEZ(' . implode(',', $curveWKT) . ')';
    }

    /*public function toGeoJSON(): array
    {
        $curveGeoJSON = array_map(fn($curve) => $curve->toGeoJSON(), $this->curves);
        return [
            'type' => 'MultiCurveZ',
            'coordinates' => $curveGeoJSON
        ];
    }*/

    /**
     * @return array<LineStringZ|CircularStringZ|CompoundCurveZ>
     */
    public function getCurves(): array
    {
        return $this->curves;
    }

    /**
     * @param array<LineStringZ|CircularStringZ|CompoundCurveZ> $curves
     * @return $this
     */
    public function setCurves(array $curves): self
    {
        $this->curves = $curves;
        return $this;
    }
}
