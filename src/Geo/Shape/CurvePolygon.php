<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class CurvePolygon extends Geometry
{
    /** @var array<LineString|CircularString> */
    private array $boundaries;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return CurvePolygon
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): CurvePolygon
    {
        // GeoJSON does not support CircularString by spec
        throw new \RuntimeException('GeoJSON does not support CurvePolygon. Use EWKT instead.');
    }

    /**
     * @param string $ewktString
     * @return CurvePolygon
     */
    public static function createFromGeoEWKTString(string $ewktString): CurvePolygon
    {
        // Extract the SRID and geometry part from the EWKT string.
        if (strpos($ewktString, 'CURVEPOLYGON') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for CurvePolygon.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);
        preg_match('/CURVEPOLYGON\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid CurvePolygon format in EWKT.');
        }

        // Now split the boundaries inside the CurvePolygon (separated by commas)
        $geometryPart = $matches[1];

        // Regular expression to capture boundaries (both CIRCULARSTRING and LINESTRING without type)
        preg_match_all('/(CIRCULARSTRING\([^)]*\)|\([^\)]+\))/i', $geometryPart, $boundaryMatches);

        if (empty($boundaryMatches[0])) {
            throw new \InvalidArgumentException('Invalid boundaries in CurvePolygon.');
        }

        // Process each boundary found
        $boundaries = [];
        foreach ($boundaryMatches[0] as $boundaryString) {
            $boundaryString = trim($boundaryString);

            // Check if it's a CIRCULARSTRING
            if (strpos($boundaryString, 'CIRCULARSTRING') === 0) {
                // It's a CIRCULARSTRING
                $_ = CircularString::createFromGeoEWKTString("SRID=$srid;$boundaryString");
                $boundaries[] = $_;
            } elseif (strpos($boundaryString, 'LINESTRING') === 0) {
                // It's an explicitly typed LINESTRING
                $_ = LineString::createFromGeoEWKTString("SRID=$srid;$boundaryString");
                $boundaries[] = $_;
            } else {
                // It's an untyped LINESTRING (just a list of points)
                $_ = LineString::createFromGeoEWKTString("SRID=$srid;LINESTRING$boundaryString");
                $boundaries[] = $_;
            }
        }

        return new CurvePolygon($boundaries, $srid);
    }


    /**
     * @param array<LineString|CircularString> $boundaries
     * @param int|null $srid The SRID of the CurvePolygon.
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
        return 'CURVEPOLYGON(' . implode(',', $boundaryWKT) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        $boundaryGeoJSON = array_map(fn($boundary) => $boundary->toGeoJSON(), $this->boundaries);
        return [
            'type' => 'CurvePolygon',
            'coordinates' => $boundaryGeoJSON
        ];
    }

    /**
     * @return array<LineString|CircularString>
     */
    public function getBoundaries(): array
    {
        return $this->boundaries;
    }

    /**
     * @param array<LineString|CircularString> $boundaries
     * @return $this
     */
    public function setBoundaries(array $boundaries): self
    {
        $this->boundaries = $boundaries;
        return $this;
    }
}
