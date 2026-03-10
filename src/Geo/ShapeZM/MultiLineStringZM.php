<?php

namespace Milanmadar\CoolioORM\Geo\ShapeZM;

use InvalidArgumentException;

class MultiLineStringZM extends AbstractShapeZM
{
    /** @var array<LineStringZM> */
    private array $lineStrings;

    /**
     * @param array<LineStringZM> $lineStrings
     * @param int|null $srid
     */
    public function __construct(array $lineStrings, int|null $srid = null)
    {
        $this->_validateLineStrings($lineStrings);
        parent::__construct($srid);
        $this->lineStrings = $lineStrings;
    }

    /**
     * @return array<LineStringZM>
     */
    public function getLineStrings(): array
    {
        return $this->lineStrings;
    }

    /**
     * @param array<LineStringZM> $lineStrings
     * @return $this
     */
    public function setLineStrings(array $lineStrings): self
    {
        $this->_validateLineStrings($lineStrings);
        $this->lineStrings = $lineStrings;
        return $this;
    }

    /**
     * @return string
     */
    public function toWKT(): string
    {
        $lines = array_map(
            fn(LineStringZM $ls) => '(' . implode(',', array_map(
                    fn(PointZM $p) => sprintf('%s %s %s %s', $p->getX(), $p->getY(), $p->getZ(), $p->getM()),
                    $ls->getPoints()
                )) . ')',
            $this->lineStrings
        );

        return 'MULTILINESTRING ZM(' . implode(',', $lines) . ')';
    }

    /**
     * @return array<string, mixed>
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'MultiLineString',
            'coordinates' => array_map(
                fn(LineStringZM $ls) => array_map(
                    fn(PointZM $p) => [$p->getX(), $p->getY(), $p->getZ(), $p->getM()],
                    $ls->getPoints()
                ),
                $this->lineStrings
            )
        ];
    }

    /**
     * @param array<string, mixed> $jsonData
     * @param int|null $srid
     * @return MultiLineStringZM
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): MultiLineStringZM
    {
        if (!isset($srid)) $srid = $_ENV['GEO_DEFAULT_SRID'];

        if (
            !isset($jsonData['type'], $jsonData['coordinates']) ||
            $jsonData['type'] !== 'MultiLineString' ||
            !is_array($jsonData['coordinates']) ||
            empty($jsonData['coordinates'])
        ) {
            throw new InvalidArgumentException('Invalid GeoJSON for MultiLineStringZM.');
        }

        $lineStrings = [];
        foreach ($jsonData['coordinates'] as $lineCoords) {
            $points = [];
            foreach ($lineCoords as $coords) {
                if (count($coords) !== 4) {
                    throw new InvalidArgumentException('Each point must have exactly 4 coordinates for ZM.');
                }
                $points[] = new PointZM((float)$coords[0], (float)$coords[1], (float)$coords[2], (float)$coords[3], $srid);
            }
            $lineStrings[] = new LineStringZM($points, $srid);
        }

        return new MultiLineStringZM($lineStrings, $srid);
    }

    /**
     * @param string $ewktString
     * @return MultiLineStringZM
     */
    public static function createFromGeoEWKTString(string $ewktString): MultiLineStringZM
    {
        if (strpos($ewktString, ';MULTILINESTRING') === false) {
            throw new InvalidArgumentException('Invalid EWKT format for MultiLineStringZM.');
        }

        [$sridPart, $geometryPart] = explode(';', $ewktString, 2);
        if (strpos($sridPart, 'SRID=') !== 0) {
            throw new InvalidArgumentException('Invalid SRID part in EWKT string.');
        }
        $srid = (int) substr($sridPart, 5);

        preg_match('/MULTILINESTRING ?Z?M?\((.*)\)/', $geometryPart, $matches);
        if (empty($matches)) {
            throw new InvalidArgumentException('Invalid MULTILINESTRING ZM format in EWKT.');
        }

        $linesData = explode('),', $matches[1]);
        $lineStrings = [];

        foreach ($linesData as $lineData) {
            $lineData = trim($lineData, " ()");
            $pointDataArr = explode(',', $lineData);
            $points = [];
            foreach ($pointDataArr as $pointData) {
                $coords = array_map('trim', explode(' ', $pointData));
                if (count($coords) !== 4) {
                    throw new InvalidArgumentException('Each point must have exactly 4 coordinates for ZM.');
                }
                $points[] = new PointZM((float)$coords[0], (float)$coords[1], (float)$coords[2], (float)$coords[3], $srid);
            }
            $lineStrings[] = new LineStringZM($points, $srid);
        }

        return new MultiLineStringZM($lineStrings, $srid);
    }

    /**
     * @param array<LineStringZM> $lineStrings
     * @throws InvalidArgumentException
     */
    private function _validateLineStrings(array $lineStrings): void
    {
        if (empty($lineStrings)) {
            throw new InvalidArgumentException('MultiLineStringZM must contain at least one LineStringZM.');
        }
        foreach ($lineStrings as $ls) {
            if (!($ls instanceof LineStringZM)) {
                throw new InvalidArgumentException('All elements must be instances of LineStringZM.');
            }
            if (count($ls->getPoints()) < 2) {
                throw new InvalidArgumentException('Each LineStringZM must have at least 2 points.');
            }
        }
    }
}