<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

class LineStringZM extends AbstractShapeZM
{
    /** @var PointZM[] */
    private array $points;

    /**
     * @param array<PointZM> $points
     * @param int|null $srid
     */
    public function __construct(array $points, int|null $srid = null)
    {
        if (empty($points)) {
            throw new \InvalidArgumentException('LineStringZM requires at least one PointZM.');
        }

        if(!isset($srid)) $srid = $points[0]->getSrid();
        parent::__construct($srid);
        $this->points = $points;
    }

    /** @return PointZM[] */
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
        if(count($points) < 2) {
            throw new \InvalidArgumentException("A LineStringZM must have at least two points.");
        }
        $this->points = $points;
        return $this;
    }

    public function toWKT(): string
    {
        $coords = array_map(fn(PointZM $pt) => implode(' ', $pt->getCoordinates()), $this->points);
        return 'LINESTRING ZM(' . implode(',', $coords) . ')';
    }

    public function toEWKT(): string
    {
        return 'SRID=' . $this->getSRID() . ';' . $this->toWKT();
    }

    public function ST_GeomFromEWKT(): string
    {
        return "ST_GeomFromEWKT('{$this->toEWKT()}')";
    }

    /**
     * @return array<string, mixed>
     */
    public function toGeoJSON(): array
    {
        $coords = array_map(fn(PointZM $pt) => $pt->getCoordinates(), $this->points);
        return [
            'type' => 'LineString',
            'coordinates' => $coords,
        ];
    }

    /**
     * @param array<string, mixed> $jsonData
     * @param int|null $srid
     * @return LineStringZM
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): LineStringZM
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'LineString' ||
            !is_array($jsonData['coordinates'])
        ) {
            throw new \InvalidArgumentException('Invalid GeoJSON for LineStringZM');
        }

        $points = [];
        foreach ($jsonData['coordinates'] as $coord) {
            if (!is_array($coord) || count($coord) !== 4) {
                throw new \InvalidArgumentException('Each coordinate must have 4 elements [X,Y,Z,M].');
            }
            $points[] = new PointZM($coord[0], $coord[1], $coord[2], $coord[3], $srid);
        }

        return new LineStringZM($points, $srid);
    }

    /**
     * @param string $ewktString
     * @return LineStringZM
     */
    public static function createFromGeoEWKTString(string $ewktString): LineStringZM
    {
        if (strpos($ewktString, 'LINESTRING') === false) {
            throw new \InvalidArgumentException('Invalid EWKT format for LineStringZM.');
        }

        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) != 2) {
            throw new \InvalidArgumentException('Invalid EWKT string: missing SRID or geometry.');
        }

        $srid = (int) substr($ewktParts[0], 5);
        $geometryPart = $ewktParts[1];

        preg_match('/LINESTRING ?Z?M?\((.+)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid LINESTRING ZM format in EWKT.');
        }

        $coordString = $matches[1];
        $coordPairs = explode(',', $coordString);
        $points = [];
        foreach ($coordPairs as $pair) {
            /** @var array<float|int> $nums */
            $nums = preg_split('/\s+/', trim($pair, '() '));
            if (count($nums) !== 4) {
                throw new \InvalidArgumentException('Each coordinate must have 4 elements [X,Y,Z,M].');
            }
            $points[] = new PointZM((float)$nums[0], (float)$nums[1], (float)$nums[2], (float)$nums[3], $srid);
        }

        return new LineStringZM($points, $srid);
    }

    public function getStartPointZM(): PointZM
    {
        return $this->points[0];
    }

    public function getEndPointZM(): PointZM
    {
        return $this->points[count($this->points) - 1];
    }
}