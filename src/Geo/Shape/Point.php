<?php

namespace Milanmadar\CoolioORM\Geo\Shape;

class Point extends Geometry
{
    private float $x;
    private float $y;

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return Point
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): static
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'Point' ||
            !is_array($jsonData['coordinates']) ||
            count($jsonData['coordinates']) !== 2
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for Point');
        }

        return new self($jsonData['coordinates'][0], $jsonData['coordinates'][1], $srid);
    }

    /**
     * @param string $ewktString
     * @return Point
     */
    public static function createFromGeoEWKTString(string $ewktString): static
    {
        // Parse the EWKT string, expected format: SRID=<srid>;POINT(<x> <y>)
        if (strpos($ewktString, 'POINT') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected POINT type.');
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

        // Validate and extract the POINT coordinates
        preg_match('/POINT\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid POINT format in EWKT.');
        }

        $coords = array_map('trim', explode(' ', $matches[1]));
        if (count($coords) !== 2) {
            throw new \InvalidArgumentException('A POINT must have exactly 2 coordinates.');
        }

        return new static((float) $coords[0], (float) $coords[1], $srid);
    }

    public function __construct(float $x, float $y, int|null $srid = null)
    {
        parent::__construct($srid);
        $this->x = $x;
        $this->y = $y;
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function setX(float $x): self
    {
        $this->x = $x;
        return $this;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function setY(float $y): self
    {
        $this->y = $y;
        return $this;
    }

    public function getCoordinates(): array
    {
        return [$this->x, $this->y];
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        return sprintf('POINT(%F %F)', $this->x, $this->y);
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$this->x, $this->y],
        ];
    }
}