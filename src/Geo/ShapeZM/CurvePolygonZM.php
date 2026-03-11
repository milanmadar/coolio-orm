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

        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) !== 2) {
            throw new \InvalidArgumentException('Invalid EWKT string: missing SRID or geometry.');
        }

        $srid = (int) substr($ewktParts[0], 5);
        $geometryPart = $ewktParts[1];

        preg_match('/CURVEPOLYGON ?Z?M?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid CurvePolygonZM format in EWKT.');
        }

        $geometryContent = $matches[1];

        // Split boundaries (LineStringZM or CircularStringZM)
        $boundaryMatches = [];
        preg_match_all('/(CIRCULARSTRING ?Z?M?\([^\)]*\)|LINESTRING ?Z?M?\([^\)]*\))/i', $geometryContent, $boundaryMatches);

        if (empty($boundaryMatches[0])) {
            throw new \InvalidArgumentException('Invalid boundaries in CurvePolygonZM.');
        }

        $boundaries = [];
        foreach ($boundaryMatches[0] as $boundaryString) {
            $boundaryString = trim($boundaryString);

            if (str_starts_with($boundaryString, 'CIRCULARSTRING')) {
                $boundaries[] = CircularStringZM::createFromGeoEWKTString("SRID=$srid;$boundaryString");
            } elseif (str_starts_with($boundaryString, 'LINESTRING')) {
                $boundaries[] = LineStringZM::createFromGeoEWKTString("SRID=$srid;$boundaryString");
            } else {
                // Untyped boundary, assume LineStringZM
                $boundaries[] = LineStringZM::createFromGeoEWKTString("SRID=$srid;LINESTRINGZM$boundaryString");
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