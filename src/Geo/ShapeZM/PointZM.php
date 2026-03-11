<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

class PointZM extends AbstractShapeZM
{
    private float $x;
    private float $y;
    private float $z;
    private float $m;

    public function __construct(float $x, float $y, float $z, float $m, int|null $srid = null)
    {
        parent::__construct($srid);
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->m = $m;
    }

    /**
     * @param array<string, mixed> $jsonData
     * @param int|null $srid
     * @return PointZM
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): PointZM
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'Point' ||
            !is_array($jsonData['coordinates']) ||
            count($jsonData['coordinates']) < 3
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for PointZM');
        }

        $coords = $jsonData['coordinates'];

        // If 4th element missing, default to 0
        $m = $coords[3] ?? 0;

        return new PointZM((float)$coords[0], (float)$coords[1], (float)$coords[2], (float)$m, $srid);
    }

    /**
     * @param string $ewktString
     * @return PointZM
     */
    public static function createFromGeoEWKTString(string $ewktString): PointZM
    {
        // Example: SRID=4326;POINT ZM(x y z m)
        if (strpos($ewktString, ';POINT') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for PointZM.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) != 2) {
            throw new \InvalidArgumentException('Invalid EWKT string, could not find SRID and geometry parts.');
        }

        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT string.');
        }

        $srid = (int) substr($sridPart, 5);

        // Parse POINT ZM(x y z m)
        preg_match('/POINT ?Z?M?\(([-0-9\.]+) ([-0-9\.]+) ([-0-9\.]+) ([-0-9\.]+)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid POINTZM format in EWKT.');
        }

        return new PointZM((float)$matches[1], (float)$matches[2], (float)$matches[3], (float)$matches[4], $srid);
    }

    public function toWKT(): string
    {
        return sprintf('POINT ZM(%s %s %s %s)', $this->x, $this->y, $this->z, $this->m);
    }

    public function toEWKT(): string
    {
        return 'SRID=' . $this->getSRID() . ';' . $this->toWKT();
    }

    public function ST_GeomFromEWKT(): string
    {
        return "ST_GeomFromEWKT('" . $this->toEWKT() . "')";
    }

    /**
     * @return array<string, mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$this->x, $this->y, $this->z, $this->m],
        ];
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function getZ(): float
    {
        return $this->z;
    }

    public function getM(): float
    {
        return $this->m;
    }

    public function setX(float $x): self
    {
        $this->x = $x;
        return $this;
    }

    public function setY(float $y): self
    {
        $this->y = $y;
        return $this;
    }

    public function setZ(float $z): self
    {
        $this->z = $z;
        return $this;
    }

    public function setM(float $m): self
    {
        $this->m = $m;
        return $this;
    }

    /**
     * @return array<float>
     */
    public function getCoordinates(): array
    {
        return [$this->x, $this->y, $this->z, $this->m];
    }

    public function equals(PointZM $other): bool
    {
        return (
            $this->x === $other->getX() &&
            $this->y === $other->getY() &&
            $this->z === $other->getZ()
            // The last one is not actually a coordinate, but usually a timestamp or measure, so we might want to ignore it in equality checks
            //&& $this->m === $other->getM()
        );
    }
}