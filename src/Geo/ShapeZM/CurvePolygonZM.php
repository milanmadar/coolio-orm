<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

class CurvePolygonZM extends AbstractShapeZM
{
    /** @var array<LineStringZM|CircularStringZM> */
    private array $boundaries;

    /**
     * @param array<string, mixed> $jsonData
     * @param int|null $srid
     * @return CurvePolygonZM
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): CurvePolygonZM
    {
        throw new \RuntimeException('GeoJSON does not support CurvePolygonZM. Use EWKT instead.');
    }

    /**
     * @param string $ewktString
     * @return CurvePolygonZM
     */
    public static function createFromGeoEWKTString(string $ewktString): CurvePolygonZM
    {
        if (strpos($ewktString, 'CURVEPOLYGON') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for CurvePolygonZM.');
        }

        // Split SRID and geometry
        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) !== 2) {
            throw new \InvalidArgumentException('Invalid EWKT string: missing SRID or geometry.');
        }

        $srid = (int) substr($ewktParts[0], 5);
        $geometryPart = $ewktParts[1];

        // Extract the content inside CURVEPOLYGON(...)
        preg_match('/CURVEPOLYGON ?Z?M?\((.*)\)/i', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid CurvePolygonZM format in EWKT.');
        }
        $content = $matches[1];

        // Parentheses-aware split for segments
        $segments = [];
        $buffer = '';
        $depth = 0;
        $len = strlen($content);

        for ($i = 0; $i < $len; $i++) {
            $ch = $content[$i];

            if ($ch === '(') $depth++;
            if ($ch === ')') $depth--;

            // Split at commas at depth 0 (between segments)
            if ($depth === 0 && $ch === ',') {
                if (trim($buffer) !== '') $segments[] = trim($buffer);
                $buffer = '';
            } else {
                $buffer .= $ch;
            }
        }
        if (trim($buffer) !== '') {
            $segments[] = trim($buffer);
        }

        $boundaries = [];
        foreach ($segments as $seg) {
            $seg = trim($seg);

            if (str_starts_with(strtoupper($seg), 'CIRCULARSTRING')) {
                $boundaries[] = CircularStringZM::createFromGeoEWKTString("SRID=$srid;$seg");
            } elseif (str_starts_with(strtoupper($seg), 'LINESTRING')) {
                $boundaries[] = LineStringZM::createFromGeoEWKTString("SRID=$srid;$seg");
            } else {
                // Implicit LineString, just parentheses with points
                $boundaries[] = LineStringZM::createFromGeoEWKTString("SRID=$srid;LINESTRING ZM$seg");
            }
        }

        return new CurvePolygonZM($boundaries, $srid);
    }

    /**
     * @param array<LineStringZM|CircularStringZM> $boundaries
     * @param int|null $srid
     */
    public function __construct(array $boundaries, int|null $srid = null)
    {
        parent::__construct($srid);
        if(empty($boundaries)) {
            throw new \InvalidArgumentException('CurvePolygonZM must have at least one boundary.');
        }
        $this->boundaries = $boundaries;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $boundaryWKT = array_map(fn($b) => $b->toWKT(), $this->boundaries);
        return 'CURVEPOLYGON ZM(' . implode(',', $boundaryWKT) . ')';
    }

    /**
     * @return array<string, mixed>
     */
    public function toGeoJSON(): array
    {
        throw new \RuntimeException('GeoJSON does not support CurvePolygonZM. Use EWKT instead.');
    }

    /**
     * @return array<LineStringZM|CircularStringZM>
     */
    public function getBoundaries(): array
    {
        return $this->boundaries;
    }

    /**
     * @param array<LineStringZM|CircularStringZM> $boundaries
     * @return $this
     */
    public function setBoundaries(array $boundaries): self
    {
        $this->boundaries = $boundaries;
        return $this;
    }
}