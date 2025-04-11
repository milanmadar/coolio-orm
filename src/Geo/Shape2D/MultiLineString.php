<?php

namespace Milanmadar\CoolioORM\Geo\Shape2D;

class MultiLineString extends AbstractShape2D
{
    /** @var array<LineString> */
    private array $lineStrings;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return MultiLineString
     */
    /*public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): MultiLineString
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'MultiLineString' ||
            !is_array($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for MultiLineString');
        }

        $lineStrings = [];

        foreach ($jsonData['coordinates'] as $lineCoords) {
            if (!is_array($lineCoords) || count($lineCoords) < 2) {
                throw new \InvalidArgumentException('Each LineString must have at least 2 coordinates.');
            }

            $points = [];

            foreach ($lineCoords as $coords) {
                if (!is_array($coords) || count($coords) !== 2) {
                    throw new \InvalidArgumentException('Invalid coordinate in MultiLineString');
                }
                $points[] = new Point((float)$coords[0], (float)$coords[1], $srid);
            }

            $lineStrings[] = new LineString($points, $srid);
        }

        return new MultiLineString($lineStrings, $srid);
    }*/

    /**
     * @param string $ewktString
     * @return MultiLineString
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiLineString
    {
        // Parse the EWKT string, expected format: SRID=<srid>;MULTILINESTRING((<x1> <y1>, <x2> <y2>, ...), ...)
        if (strpos($ewktString, 'MULTILINESTRING') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected MULTILINESTRING type.');
        }

        // Extract the SRID and the WKT string
        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) != 2) {
            throw new \InvalidArgumentException('Invalid EWKT string, could not find SRID and geometry parts.');
        }

        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        // Extract SRID value
        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);

        // Validate and extract the MULTILINESTRING coordinates
        preg_match('/MULTILINESTRING\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid MULTILINESTRING format in EWKT.');
        }

        $lineStringsData = explode('),', $matches[1]);
        $lineStrings = [];

        foreach ($lineStringsData as $lineStringData) {
            // Clean up the line string (remove extra spaces and commas)
            $lineStringData = trim($lineStringData);
            if ($lineStringData[0] === '(') {
                $lineStringData = substr($lineStringData, 1);
            }
            if (substr($lineStringData, -1) === ')') {
                $lineStringData = substr($lineStringData, 0, -1);
            }

            $pointsData = explode(',', $lineStringData);
            $points = [];

            foreach ($pointsData as $pointData) {
                $coords = array_map('trim', explode(' ', $pointData));
                if (count($coords) !== 2) {
                    throw new \InvalidArgumentException('Each point in the LineString must have exactly 2 coordinates.');
                }

                $points[] = new Point((float) $coords[0], (float) $coords[1], $srid);
            }

            $lineStrings[] = new LineString($points, $srid);
        }

        return new MultiLineString($lineStrings, $srid);
    }

    /**
     * @param array<LineString> $lineStrings
     * @param int|null $srid
     */
    public function __construct(array $lineStrings, int|null $srid = null)
    {
        if (empty($lineStrings)) {
            throw new \InvalidArgumentException('MultiLineString must contain at least one LineString.');
        }

        parent::__construct($srid);
        $this->lineStrings = $lineStrings;
    }

    /**
     * @return array<LineString>
     */
    public function getLineStrings(): array
    {
        return $this->lineStrings;
    }

    /**
     * @param array<LineString> $lineStrings
     * @return $this
     */
    public function setLineStrings(array $lineStrings): MultiLineString
    {
        if (empty($lineStrings)) {
            throw new \InvalidArgumentException('MultiLineString must contain at least one LineString.');
        }

        $this->lineStrings = $lineStrings;
        return $this;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $segments = array_map(
            fn(LineString $ls) => '(' . implode(',', array_map(
                    fn(Point $p) => sprintf('%s %s', $p->getX(), $p->getY()),
                    $ls->getPoints()
                )) . ')',
            $this->lineStrings
        );

        return 'MULTILINESTRING(' . implode(',', $segments) . ')';
    }

    /*public function toGeoJSON(): array
    {
        return [
            'type' => 'MultiLineString',
            'coordinates' => array_map(
                fn(LineString $ls) => array_map(
                    fn(Point $p) => [$p->getX(), $p->getY()],
                    $ls->getPoints()
                ),
                $this->lineStrings
            )
        ];
    }*/
}
