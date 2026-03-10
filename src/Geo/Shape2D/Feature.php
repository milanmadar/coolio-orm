<?php

namespace Milanmadar\CoolioORM\Geo\Shape2D;

use Milanmadar\CoolioORM\Geo\AbstractShape;
use Milanmadar\CoolioORM\Geo\Shape2D3DFactory;

class Feature extends AbstractShape2D
{
    private AbstractShape $geometry;

    /** @var array<string, mixed>|null */
    private ?array $properties;

    /** @var string|int|float|null */
    private string|int|null $id = null;

    /**
     * {
     *   "type": "Feature",
     *   "geometry": { ... valid geometry ... },
     *   "properties": { ... } | null,
     *   "id": number|string|null
     * }
     *
     * @param array<string, mixed> $jsonData
     * @param int|null $srid Optional SRID, defaults to the value in $_ENV['GEO_DEFAULT_SRID']
     * @return Feature
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): Feature
    {
        if (!isset($jsonData['geometry'])) {
            throw new \InvalidArgumentException('Feature GeoJSON must have a "geometry" key.');
        }

        // Use your factory to parse the geometry
        $geometry = Shape2D3DFactory::createFromGeoJSON($jsonData['geometry'], $srid);

        $properties = $jsonData['properties'] ?? null;
        $id = $jsonData['id'] ?? null;

        return new Feature($geometry, $properties, $id, $srid);
    }

    /**
     * (Properties cannot be encoded in EWKT - this method only extracts geometry)
     *
     * @param string $ewktString
     * @return Feature
     */
    public static function createFromGeoEWKTString(string $ewktString): Feature
    {
        $geometry = Shape2D3DFactory::createFromGeoEWKTString($ewktString);

        // EWKT cannot carry properties or id
        return new Feature($geometry, null, null, $geometry->getSrid());
    }

    /**
     * @param AbstractShape $geometry
     * @param array<string, mixed>|null $properties
     * @param string|int|float|null $id
     * @param int|null $srid
     */
    public function __construct(AbstractShape $geometry, ?array $properties = null, string|int|float|null $id = null, int|null $srid = null)
    {
        parent::__construct($srid);
        $this->geometry = $geometry;
        $this->properties = $properties;
        $this->id = $id;
    }

    public function getGeometry(): AbstractShape
    {
        return $this->geometry;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }

    /**
     * @param array<string, mixed>|null $properties
     * @return $this
     */
    public function setProperties(?array $properties): self
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setProperty(string $name, mixed $value): self
    {
        $this->properties[$name] = $value;
        return $this;
    }

    public function getId(): string|int|float|null
    {
        return $this->id;
    }

    /**
     * Serialize Feature back to GeoJSON.
     */
    public function toGeoJSON(): array
    {
        $data = [
            'type' => 'Feature',
            'geometry' => $this->geometry->toGeoJSON(),
            'properties' => $this->properties,
        ];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        return $data;
    }

    public function toWKT(): string
    {
        return $this->geometry->toWKT();
    }
}