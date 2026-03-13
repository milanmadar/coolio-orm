<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

class CircularStringZM extends AbstractShapeZM implements HasStartEndPointZMInterface
{
    /** @var array<PointZM> */
    private array $points;

    /**
     * @param array<PointZM> $points
     * @param int|null $srid
     */
    public function __construct(array $points, int|null $srid = null)
    {
        if(empty($points)) {
            throw new \InvalidArgumentException('CircularStringZM must have at least 3 points.');
        }
        if(!isset($srid)) $srid = $points[0]->getSrid();
        $this->_validatePoints($points);
        parent::__construct($srid);
        $this->points = $points;
    }

    /**
     * @param string $ewktString
     * @return CircularStringZM
     */
    public static function createFromGeoEWKTString(string $ewktString): CircularStringZM
    {
        if (!str_contains($ewktString, 'CIRCULARSTRING')) {
            throw new \InvalidArgumentException('Invalid EWKT format. Expected CIRCULARSTRINGZM type.');
        }

        // Split SRID and geometry
        $ewktParts = explode(';', $ewktString, 2);
        if (count($ewktParts) !== 2) {
            throw new \InvalidArgumentException('Invalid EWKT string, missing SRID or geometry.');
        }

        $sridPart = $ewktParts[0];
        $geometryPart = $ewktParts[1];

        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new \InvalidArgumentException('Invalid SRID part in EWKT.');
        }

        $srid = (int) substr($sridPart, 5);

        preg_match('/CIRCULARSTRING ?Z?M?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException('Invalid CIRCULARSTRINGZM format in EWKT.');
        }

        $pointsData = array_map('trim', explode(',', $matches[1]));
        $points = [];

        foreach ($pointsData as $pointData) {
            /** @var array<string> $_ */
            $_ = preg_split('/\s+/', $pointData);
            $coords = array_map('trim', $_);
            if (count($coords) !== 4) {
                throw new \InvalidArgumentException('Each point in CircularStringZM must have 4 coordinates.');
            }

            $points[] = new PointZM(
                (float) $coords[0],
                (float) $coords[1],
                (float) $coords[2],
                (float) $coords[3],
                $srid
            );
        }

        return new CircularStringZM($points, $srid);
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
        $this->_validatePoints($points);
        $this->points = $points;
        return $this;
    }

    /**
     * @param array<PointZM> $points
     * @throws \InvalidArgumentException
     */
    private function _validatePoints(array $points): void
    {
        if (count($points) < 3 || count($points) % 2 === 0) {
            throw new \InvalidArgumentException('A CircularStringZM must have an odd number of points ≥ 3.');
        }
    }

    public function toWKT(): string
    {
        $pointStrings = array_map(
            fn(PointZM $p) => sprintf('%s %s %s %s', $p->getX(), $p->getY(), $p->getZ(), $p->getM()),
            $this->points
        );

        return 'CIRCULARSTRING ZM(' . implode(',', $pointStrings) . ')';
    }

    /**
     * GeoJSON does not support CircularStringZM
     * @throws \RuntimeException
     */
    public function toGeoJSON(): array
    {
        throw new \RuntimeException('GeoJSON does not support CircularStringZM.');
    }

    public function getStartPointZM(): PointZM
    {
        return $this->points[0];
    }

    public function getEndPointZM(): PointZM
    {
        return $this->points[count($this->points) - 1];
    }

    public static function createFromGeoJSON(array $jsonData, ?int $srid = null): AbstractShapeZM
    {
        throw new \RuntimeException('GeoJSON does not support CircularStringZM. Use EWKT instead.');
    }
}