<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

use InvalidArgumentException;

class MultiPointZM extends AbstractShapeZM
{
    /** @var array<PointZM> */
    private array $points;

    /**
     * @param array<PointZM> $points
     * @param int|null $srid
     */
    public function __construct(array $points, int|null $srid = null)
    {
        if (empty($points)) {
            throw new InvalidArgumentException('A MultiPointZM must contain at least one PointZM.');
        }

        parent::__construct($srid);
        $this->points = $points;
    }

    /**
     * @return array<PointZM>
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    /**
     * @param array<PointZM> $points
     * @return $this
     */
    public function setPoints(array $points): self
    {
        if (empty($points)) {
            throw new InvalidArgumentException('A MultiPointZM must contain at least one PointZM.');
        }
        $this->points = $points;
        return $this;
    }

    /**
     * @param array<string, mixed> $jsonData
     * @param int|null $srid
     * @return MultiPointZM
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): MultiPointZM
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (!isset($jsonData['type'], $jsonData['coordinates']) || $jsonData['type'] !== 'MultiPoint') {
            throw new InvalidArgumentException('Invalid GeoJSON for MultiPointZM.');
        }

        $points = [];
        foreach ($jsonData['coordinates'] as $coord) {
            if (count($coord) !== 4) {
                throw new InvalidArgumentException('Each coordinate must have 4 values for ZM.');
            }
            $points[] = new PointZM((float)$coord[0], (float)$coord[1], (float)$coord[2], (float)$coord[3], $srid);
        }

        return new MultiPointZM($points, $srid);
    }

    /**
     * @param string $ewktString
     * @return MultiPointZM
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiPointZM
    {
        if (strpos($ewktString, ';MULTIPOINT') === false) {
            throw new InvalidArgumentException('Invalid EWKT format. Expected MULTIPOINT ZM type.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) != 2) {
            throw new InvalidArgumentException('Invalid EWKT string: cannot split SRID and geometry.');
        }

        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new InvalidArgumentException('Invalid SRID part in EWKT.');
        }

        $srid = (int) substr($sridPart, 5);

        preg_match('/MULTIPOINT ?Z?M?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new InvalidArgumentException('Invalid MULTIPOINT ZM format in EWKT.');
        }

        $pointStrings = explode(',', $matches[1]);
        $points = [];
        foreach ($pointStrings as $pStr) {
            $coords = array_map('trim', explode(' ', trim($pStr, '()')));
            if (count($coords) !== 4) {
                throw new InvalidArgumentException('Each point in MULTIPOINT ZM must have 4 coordinates.');
            }
            $points[] = new PointZM((float)$coords[0], (float)$coords[1], (float)$coords[2], (float)$coords[3], $srid);
        }

        return new MultiPointZM($points, $srid);
    }

    public function toWKT(): string
    {
        $pointStrings = array_map(
            fn(PointZM $p) => sprintf('(%s %s %s %s)', $p->getX(), $p->getY(), $p->getZ(), $p->getM()),
            $this->points
        );

        return 'MULTIPOINT ZM(' . implode(',', $pointStrings) . ')';
    }

    /**
     * @return array<string, mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'MultiPoint',
            'coordinates' => array_map(
                fn(PointZM $p) => $p->getCoordinates(),
                $this->points
            )
        ];
    }
}