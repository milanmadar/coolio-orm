<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class CompoundCurve extends Geometry implements HasStartEndPointInterface
{
    /** @var array<LineString|CircularString> */
    private array $segments;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return CompoundCurve
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): CompoundCurve
    {
        // GeoJSON does not support CircularString by spec
        throw new \RuntimeException('GeoJSON does not support CompoundCurve. Use EWKT instead.');
    }

    /**
     * Creates a CompoundCurve from a GeoEWKT string.
     * @param string $ewktString
     * @return CompoundCurve
     */
    public static function createFromGeoEWKTString(string $ewktString): CompoundCurve
    {
        // Extract the SRID and geometry part from the EWKT string.
        if (strpos($ewktString, 'COMPOUNDCURVE') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for CompoundCurve.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);
        preg_match('/COMPOUNDCURVE\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid CompoundCurve format in EWKT.');
        }

        // Now we need to split the segments inside the CompoundCurve.
        // We will use a more careful approach to handle commas within parentheses.
        $geometryPart = $matches[1];
        $segments = [];
        $parenCount = 0;
        $currentSegment = '';
        $inSegment = false;

        // Iterate through the geometry part and properly extract the segments
        for ($i = 0; $i < strlen($geometryPart); $i++) {
            $char = $geometryPart[$i];
            if ($char === '(') {
                $parenCount++;
                $inSegment = true;
            } elseif ($char === ')') {
                $parenCount--;
            }

            // We only split the segments when the parentheses are balanced
            if ($parenCount === 0 && $char === ',') {
                // End of one segment, add it to the segments array
                $segments[] = trim($currentSegment);
                $currentSegment = '';
                $inSegment = false;
            } else {
                // Continue building the current segment
                $currentSegment .= $char;
            }
        }

        // Add the last segment
        if (!empty($currentSegment)) {
            $segments[] = trim($currentSegment);
        }

        // Now process the segments to create LineStrings and CircularStrings
        $processedSegments = [];
        foreach ($segments as $segmentString) {
            // If the segment doesn't contain a geometry type, treat it as a LineString
            if (!str_starts_with($segmentString, 'LINESTRING') && !str_starts_with($segmentString, 'CIRCULARSTRING')) {
                // Treat it as a LineString
                $segmentString = 'LINESTRING(' . $segmentString . ')';
                $processedSegments[] = LineString::createFromGeoEWKTString("SRID=$srid;$segmentString");
            } elseif (strpos($segmentString, 'LINESTRING') !== false) {
                $processedSegments[] = LineString::createFromGeoEWKTString("SRID=$srid;$segmentString");
            } elseif (strpos($segmentString, 'CIRCULARSTRING') !== false) {
                $processedSegments[] = CircularString::createFromGeoEWKTString("SRID=$srid;$segmentString");
            } else {
                throw new \InvalidArgumentException("Invalid segment type in CompoundCurve: $segmentString");
            }
        }

        return new CompoundCurve($processedSegments, $srid);
    }

    /**
     * CompoundCurve constructor.
     * @param array<LineString|CircularString> $segments An array of LineString or CircularString objects.
     * @param int|null $srid The SRID of the CompoundCurve.
     */
    public function __construct(array $segments, int|null $srid = null)
    {
        $this->_validateSegments($segments);
        parent::__construct($srid);
        $this->segments = $segments;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $segmentWKT = array_map(fn($segment) => $segment->toWKT(), $this->segments);
        return 'COMPOUNDCURVE(' . implode(',', $segmentWKT) . ')';
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        $segmentGeoJSON = array_map(fn($segment) => $segment->toGeoJSON(), $this->segments);
        return [
            'type' => 'MultiCurve',
            'coordinates' => $segmentGeoJSON
        ];
    }

    /**
     * @return array<LineString|CircularString>
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * @param array<LineString|CircularString> $segments
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setSegments(array $segments): CompoundCurve
    {
        $this->_validateSegments($segments);
        $this->segments = $segments;
        return $this;
    }

    /**
     * @param array<LineString|CircularString> $segments
     * @return void
     * @throws \InvalidArgumentException
     */
    private function _validateSegments(array $segments): void
    {
        // Verify continuity: end of one segment must match the start of the next
        for ($i = 0; $i < count($segments) - 1; $i++) {
            $currentSegment = $segments[$i];
            $nextSegment = $segments[$i + 1];

            $currentEndPoint = $currentSegment->getEndPoint();
            $nextStartPoint = $nextSegment->getStartPoint();

            if ($currentEndPoint != $nextStartPoint) {
                throw new \InvalidArgumentException("Segments are not continuous. End point of one segment does not match the start point of the next.");
            }
        }
    }

    public function getStartPoint(): Point
    {
        return $this->segments[0]->getStartPoint();
    }

    public function getEndPoint(): Point
    {
        return $this->segments[count($this->segments) - 1]->getEndPoint();
    }
}

