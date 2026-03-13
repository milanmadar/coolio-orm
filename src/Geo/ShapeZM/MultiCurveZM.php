<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

class MultiCurveZM extends AbstractShapeZM
{
    /** @var array<LineStringZM|CircularStringZM|CompoundCurveZM> */
    private array $curves;

    /**
     * @param array<string, mixed> $jsonData
     * @param int|null $srid Optional SRID
     * @return MultiCurveZM
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): MultiCurveZM
    {
        throw new \RuntimeException('GeoJSON does not support MultiCurveZM. Use EWKT instead.');
    }

    /**
     * @param string $ewktString
     * @return MultiCurveZM
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiCurveZM
    {
        if (strpos($ewktString, 'MULTICURVE') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for MultiCurveZM.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) !== 2) {
            throw new \InvalidArgumentException('Invalid EWKT string: missing SRID or geometry.');
        }

        $srid = (int) substr($ewktParts[0], 5);
        $geometryPart = $ewktParts[1];

        preg_match('/MULTICURVE ?Z?M?\((.*)\)/i', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid MULTICURVEZM format in EWKT.');
        }

        $geometryContent = $matches[1];

        // Split individual curves safely, respecting parentheses
        $curves = [];
        $parenCount = 0;
        $currentCurve = '';

        for ($i = 0; $i < strlen($geometryContent); $i++) {
            $char = $geometryContent[$i];
            if ($char === '(') {
                $parenCount++;
            } elseif ($char === ')') {
                $parenCount--;
            }

            if ($parenCount === 0 && $char === ',') {
                $curves[] = trim($currentCurve);
                $currentCurve = '';
            } else {
                $currentCurve .= $char;
            }
        }

        if (!empty($currentCurve)) {
            $curves[] = trim($currentCurve);
        }

        $processedCurves = [];
        foreach ($curves as $curveString) {
            $curveString = trim($curveString);

            if (stripos($curveString, 'CIRCULARSTRING') === 0) {
                $processedCurves[] = CircularStringZM::createFromGeoEWKTString("SRID=$srid;$curveString");
            } elseif (stripos($curveString, 'COMPOUNDCURVE') === 0) {
                $processedCurves[] = CompoundCurveZM::createFromGeoEWKTString("SRID=$srid;$curveString");
            } elseif (stripos($curveString, 'LINESTRING') === 0) {
                $processedCurves[] = LineStringZM::createFromGeoEWKTString("SRID=$srid;$curveString");
            } else {
                // Untyped = assume ZM LineString
                $processedCurves[] = LineStringZM::createFromGeoEWKTString("SRID=$srid;LINESTRING ZM$curveString");
            }
        }

        return new MultiCurveZM($processedCurves, $srid);
    }

    /**
     * @param array<LineStringZM|CircularStringZM|CompoundCurveZM> $curves
     * @param int|null $srid
     */
    public function __construct(array $curves, int|null $srid = null)
    {
        if(empty($curves)) {
            throw new \InvalidArgumentException('MultiCurveZM must contain at least one curve.');
        }
        if(!isset($srid)) $srid = $curves[0]->getSrid();
        parent::__construct($srid);
        $this->curves = $curves;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $curveWKT = array_map(fn($c) => $c->toWKT(), $this->curves);
        return 'MULTICURVE ZM(' . implode(',', $curveWKT) . ')';
    }

    /**
     * @return array<string, mixed>
     */
    public function toGeoJSON(): array
    {
        throw new \RuntimeException('GeoJSON does not support MultiCurveZM. Use EWKT instead.');
    }

    /**
     * @return array<LineStringZM|CircularStringZM|CompoundCurveZM>
     */
    public function getCurves(): array
    {
        return $this->curves;
    }

    /**
     * @param array<LineStringZM|CircularStringZM|CompoundCurveZM> $curves
     * @return $this
     */
    public function setCurves(array $curves): self
    {
        $this->curves = $curves;
        return $this;
    }
}