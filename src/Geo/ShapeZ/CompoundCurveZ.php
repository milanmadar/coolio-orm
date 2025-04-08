<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class CompoundCurveZ extends AbstractShapeZ implements HasStartEndPointZInterface
{
    /** @var array<LineStringZ|CircularStringZ> */
    private array $segments;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid
     * @return CompoundCurveZ
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): CompoundCurveZ
    {
        throw new \RuntimeException('GeoJSON does not support CompoundCurveZ. Use EWKT instead.');
    }

    /**
     * Creates a CompoundCurveZ from a GeoEWKT string.
     * @param string $ewktString
     * @return CompoundCurveZ
     */
    public static function createFromGeoEWKTString(string $ewktString): CompoundCurveZ
    {
        if (strpos($ewktString, 'COMPOUNDCURVE') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for CompoundCurveZ.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);

        preg_match('/COMPOUNDCURVE ?(Z?)\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid CompoundCurveZ format in EWKT.');
        }

        $geometryContent = $matches[2];

        $segments = [];
        $parenCount = 0;
        $currentSegment = '';

        for ($i = 0; $i < strlen($geometryContent); $i++) {
            $char = $geometryContent[$i];
            if ($char === '(') {
                $parenCount++;
            } elseif ($char === ')') {
                $parenCount--;
            }

            if ($parenCount === 0 && $char === ',') {
                $segments[] = trim($currentSegment);
                $currentSegment = '';
            } else {
                $currentSegment .= $char;
            }
        }

        if (!empty($currentSegment)) {
            $segments[] = trim($currentSegment);
        }

        $processedSegments = [];
        foreach ($segments as $segmentString) {
            if (!str_starts_with($segmentString, 'LINESTRING') && !str_starts_with($segmentString, 'CIRCULARSTRING')) {
                // Assume it’s a LineString
                $segmentString = 'LINESTRING(' . $segmentString . ')';
                $processedSegments[] = LineStringZ::createFromGeoEWKTString("SRID=$srid;$segmentString");
            } elseif (strpos($segmentString, 'LINESTRING') !== false) {
                $processedSegments[] = LineStringZ::createFromGeoEWKTString("SRID=$srid;$segmentString");
            } elseif (strpos($segmentString, 'CIRCULARSTRING') !== false) {
                $processedSegments[] = CircularStringZ::createFromGeoEWKTString("SRID=$srid;$segmentString");
            } else {
                throw new \InvalidArgumentException("Invalid segment type in CompoundCurveZ: $segmentString");
            }
        }

        return new CompoundCurveZ($processedSegments, $srid);
    }

    /**
     * CompoundCurveZ constructor.
     * @param array<LineStringZ|CircularStringZ> $segments
     * @param int|null $srid
     */
    public function __construct(array $segments, int|null $srid = null)
    {
        $this->_validateSegments($segments);
        parent::__construct($srid);
        $this->segments = $segments;
    }

    public function toWKT(): string
    {
        $segmentWKT = array_map(fn($segment) => $segment->toWKT(), $this->segments);
        return 'COMPOUNDCURVEZ(' . implode(',', $segmentWKT) . ')';
    }

    public function toGeoJSON(): array
    {
        $segmentGeoJSON = array_map(fn($segment) => $segment->toGeoJSON(), $this->segments);
        return [
            'type' => 'MultiCurve',
            'coordinates' => $segmentGeoJSON
        ];
    }

    /**
     * @return array<LineStringZ|CircularStringZ>
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * @param array<LineStringZ|CircularStringZ> $segments
     * @return $this
     */
    public function setSegments(array $segments): CompoundCurveZ
    {
        $this->_validateSegments($segments);
        $this->segments = $segments;
        return $this;
    }

    /**
     * @param array<LineStringZ|CircularStringZ> $segments
     */
    private function _validateSegments(array $segments): void
    {
        for ($i = 0; $i < count($segments) - 1; $i++) {
            $currentSegment = $segments[$i];
            $nextSegment = $segments[$i + 1];

            $currentEndPoint = $currentSegment->getEndPointZ();
            $nextStartPoint = $nextSegment->getStartPointZ();

            if ($currentEndPoint != $nextStartPoint) {
                throw new \InvalidArgumentException("Segments are not continuous. End point of one segment does not match the start point of the next.");
            }
        }
    }

    public function getStartPointZ(): PointZ
    {
        return $this->segments[0]->getStartPointZ();
    }

    public function getEndPointZ(): PointZ
    {
        return $this->segments[count($this->segments) - 1]->getEndPointZ();
    }
}
