<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class CurvePolygonZ extends AbstractShapeZ
{
    /** @var array<LineStringZ|CircularStringZ> */
    private array $boundaries;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid
     * @return CurvePolygonZ
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): CurvePolygonZ
    {
        throw new \RuntimeException('GeoJSON does not support CurvePolygonZ. Use EWKT instead.');
    }

    /**
     * @param string $ewktString
     * @return CurvePolygonZ
     */
    public static function createFromGeoEWKTString(string $ewktString): CurvePolygonZ
    {
        if (strpos($ewktString, 'CURVEPOLYGON') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for CurvePolygonZ.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);
        preg_match('/CURVEPOLYGON ?Z?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid CurvePolygonZ format in EWKT.');
        }

        $geometryPart = $matches[1];

        preg_match_all('/(CIRCULARSTRING ?Z?\([^)]*\)|LINESTRING ?Z?\([^)]*\)|\([^\)]*\))/i', $geometryPart, $boundaryMatches);

        if (empty($boundaryMatches[0])) {
            throw new \InvalidArgumentException('Invalid boundaries in CurvePolygonZ.');
        }

        $boundaries = [];
        foreach ($boundaryMatches[0] as $boundaryString) {
            $boundaryString = trim($boundaryString);

            if (strpos($boundaryString, 'CIRCULARSTRING') === 0) {
                $_ = CircularStringZ::createFromGeoEWKTString("SRID=$srid;$boundaryString");
                $boundaries[] = $_;
            } elseif (strpos($boundaryString, 'LINESTRING') === 0) {
                $_ = LineStringZ::createFromGeoEWKTString("SRID=$srid;$boundaryString");
                $boundaries[] = $_;
            } else {
                // Untyped boundary; assume it's an untyped 3D LineString
                $_ = LineStringZ::createFromGeoEWKTString("SRID=$srid;LINESTRINGZ$boundaryString");
                $boundaries[] = $_;
            }
        }

        return new CurvePolygonZ($boundaries, $srid);
    }

    /**
     * @param array<LineStringZ|CircularStringZ> $boundaries
     * @param int|null $srid
     */
    public function __construct(array $boundaries, int|null $srid = null)
    {
        parent::__construct($srid);
        $this->boundaries = $boundaries;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $boundaryWKT = array_map(fn($boundary) => $boundary->toWKT(), $this->boundaries);
        return 'CURVEPOLYGONZ(' . implode(',', $boundaryWKT) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        $boundaryGeoJSON = array_map(fn($boundary) => $boundary->toGeoJSON(), $this->boundaries);
        return [
            'type' => 'CurvePolygonZ',
            'coordinates' => $boundaryGeoJSON
        ];
    }

    /**
     * @return array<LineStringZ|CircularStringZ>
     */
    public function getBoundaries(): array
    {
        return $this->boundaries;
    }

    /**
     * @param array<LineStringZ|CircularStringZ> $boundaries
     * @return $this
     */
    public function setBoundaries(array $boundaries): self
    {
        $this->boundaries = $boundaries;
        return $this;
    }
}
