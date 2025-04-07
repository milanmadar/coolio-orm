<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZ;

class PointZ extends GeometryZ
{
    private float $x;
    private float $y;
    private float $z;

    public function __construct(float $x, float $y, float $z, int|null $srid = null)
    {
        parent::__construct($srid);
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    /**
     * @param array<mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return PointZ
     */
    public static function createFromGeoJSONData(array $jsonData, int|null $srid = null): PointZ
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'Point' ||
            !is_array($jsonData['coordinates']) ||
            count($jsonData['coordinates']) !== 3
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for PointZ');
        }

        return new PointZ($jsonData['coordinates'][0], $jsonData['coordinates'][1], $jsonData['coordinates'][2], $srid);
    }

    /**
     * @param string $ewktString
     * @return PointZ
     */
    public static function createFromGeoEWKTString(string $ewktString): PointZ
    {
        // Parse the EWKT string, expected format: SRID=<srid>;POINTZ(<x> <y> <z>)
        //if (strpos($ewktString, 'POINTZ') === false) {
        if (strpos($ewktString, ';POINT') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for PointZ.');
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

        // Validate and extract the POINTZ coordinates
        preg_match('/POINT ?Z?\(([-0-9\.]+) ([-0-9\.]+) ([-0-9\.]+)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid POINTZ format in EWKT.');
        }

        return new PointZ((float) $matches[1], (float) $matches[2], (float) $matches[3], $srid);
    }

    public function toWKT(): string
    {
        return sprintf('POINTZ(%s %s %s)', $this->x, $this->y, $this->z);
    }

    /**
     * @return array<mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$this->x, $this->y, $this->z],
        ];
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

    public function getZ(): float
    {
        return $this->z;
    }

    public function setZ(float $z): self
    {
        $this->z = $z;
        return $this;
    }

    /**
     * @return array<float>
     */
    public function getCoordinates(): array
    {
        return [$this->x, $this->y, $this->z];
    }
}
