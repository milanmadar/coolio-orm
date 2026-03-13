<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

class CompoundCurveZM extends AbstractShapeZM implements HasStartEndPointZMInterface
{
    /** @var array<LineStringZM|CircularStringZM> */
    private array $segments;

    public static function createFromGeoJSON(array $jsonData, ?int $srid = null): CompoundCurveZM
    {
        throw new \RuntimeException('GeoJSON does not support CompoundCurveZM. Use EWKT instead.');
    }

    public static function createFromGeoEWKTString(string $ewktString): CompoundCurveZM
    {
        if (!str_contains($ewktString, 'COMPOUNDCURVE')) {
            throw new \InvalidArgumentException('Invalid EWKT format for CompoundCurveZM.');
        }

        // Split SRID and geometry
        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) !== 2) {
            throw new \InvalidArgumentException('Invalid EWKT: missing SRID or geometry.');
        }

        $srid = (int) substr($ewktParts[0], 5);
        $geometryPart = $ewktParts[1];

        // Extract compound content
        preg_match('/COMPOUNDCURVE ?Z?M?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid COMPOUNDCURVE ZM format in EWKT.');
        }

        $content = $matches[1];

        // Split segments while tracking parentheses
        $segments = [];
        $current = '';
        $depth = 0;

        $len = strlen($content);
        for ($i = 0; $i < $len; $i++) {
            $ch = $content[$i];

            if ($ch === '(') $depth++;
            if ($ch === ')') $depth--;

            if ($depth === 0 && $ch === ',') {
                $segments[] = trim($current);
                $current = '';
            } else {
                $current .= $ch;
            }
        }
        if (trim($current) !== '') {
            $segments[] = trim($current);
        }

        // Convert each segment into LineStringZM or CircularStringZM
        $parsed = [];
        foreach ($segments as $seg) {
            $seg = trim($seg);

            // Implicit LineString: "(0 0 0 1, 1 1 1 2)"
            if (!str_starts_with($seg, 'LINESTRING') &&
                !str_starts_with($seg, 'CIRCULARSTRING')) {
                $seg = "LINESTRING ZM($seg)";
                $parsed[] = LineStringZM::createFromGeoEWKTString("SRID=$srid;$seg");
                continue;
            }

            if (str_starts_with($seg, 'LINESTRING')) {
                $parsed[] = LineStringZM::createFromGeoEWKTString("SRID=$srid;$seg");
                continue;
            }

            if (str_starts_with($seg, 'CIRCULARSTRING')) {
                $parsed[] = CircularStringZM::createFromGeoEWKTString("SRID=$srid;$seg");
                continue;
            }

            throw new \InvalidArgumentException("Unknown segment type: $seg");
        }

        return new CompoundCurveZM($parsed, $srid);
    }

    /**
     * @param array<LineStringZM|CircularStringZM> $segments
     * @param int|null $srid
     */
    public function __construct(array $segments, ?int $srid = null)
    {
        if(empty($segments)) {
            throw new \InvalidArgumentException("CompoundCurveZM requires at least one segment.");
        }
        if(!isset($srid)) $srid = $segments[0]->getSrid();
        $this->_validateSegments($segments);
        parent::__construct($srid);
        $this->segments = $segments;
    }

    public function toWKT(): string
    {
        $joined = implode(',', array_map(fn($s) => $s->toWKT(), $this->segments));
        return "COMPOUNDCURVE ZM($joined)";
    }

    public function toGeoJSON(): array
    {
        throw new \RuntimeException('GeoJSON does not support CompoundCurveZM.');
    }

    /**
     * @return array<LineStringZM|CircularStringZM>
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * @param array<LineStringZM|CircularStringZM> $segments
     * @return $this
     */
    public function setSegments(array $segments): CompoundCurveZM
    {
        $this->_validateSegments($segments);
        $this->segments = $segments;
        return $this;
    }

    /**
     * @param array<LineStringZM|CircularStringZM> $segments
     * @return void
     */
    private function _validateSegments(array $segments): void
    {
        if (count($segments) < 1) {
            throw new \InvalidArgumentException("CompoundCurveZM requires at least one segment.");
        }

        // Ensure continuity
        for ($i = 0; $i < count($segments) - 1; $i++) {
            $end = $segments[$i]->getEndPointZM();
            $start = $segments[$i+1]->getStartPointZM();

            if (!$end->equals($start)) {
                throw new \InvalidArgumentException("Segments are not continuous. Segment {$i} end does not match segment " . ($i+1) . " start.");
            }
        }
    }

    public function getStartPointZM(): PointZM
    {
        return $this->segments[0]->getStartPointZM();
    }

    public function getEndPointZM(): PointZM
    {
        return $this->segments[count($this->segments)-1]->getEndPointZM();
    }
}