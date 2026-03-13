<?php

namespace Milanmadar\CoolioORM\Geo;

use Milanmadar\CoolioORM\Geo\Shape2D\AbstractShape2D;

class FeatureCollection extends AbstractShape2D
{
    /** @var Feature[] */
    private array $features;

    /**
     * {
     *   "type": "FeatureCollection",
     *   "features": [ ...array of valid Features... ]
     * }
     *
     * @param array<string, mixed> $jsonData
     * @param int|null $srid Optional SRID
     * @return FeatureCollection
     */
    public static function createFromGeoJSON(array $jsonData, int|null $srid = null): FeatureCollection
    {
        if (!isset($jsonData['type']) || $jsonData['type'] !== 'FeatureCollection') {
            throw new \InvalidArgumentException('Invalid GeoJSON: expected type FeatureCollection.');
        }

        if (!isset($jsonData['features']) || !is_array($jsonData['features'])) {
            throw new \InvalidArgumentException('Invalid GeoJSON: missing or invalid "features" array.');
        }

        $features = [];
        foreach ($jsonData['features'] as $featureData) {
            $features[] = Feature::createFromGeoJSON($featureData, $srid);
        }

        return new FeatureCollection($features, $srid);
    }

    public static function createFromGeoEWKTString(string $ewktString): AbstractShape2D
    {
        throw new \RuntimeException('FeatureCollection cannot be created from an EWKT string. EWKT is for single geometries, not collections of features.');
    }

    /**
     * @param Feature[] $features
     * @param int|null $srid
     */
    public function __construct(array $features, int|null $srid = null)
    {
        if(!isset($srid)) $srid = empty($features) ? null : $features[0]->getSrid();
        parent::__construct($srid);

        $this->features = $features;
    }

    /**
     * @return Feature[]
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * FeatureCollections cannot be converted to WKT in a meaningful way.
     *
     * @throws \RuntimeException
     */
    public function toWKT(): string
    {
        throw new \RuntimeException('FeatureCollection cannot be represented as a single WKT geometry.');
    }

    /**
     * FeatureCollections cannot be converted to EWKT in a meaningful way.
     *
     * @throws \RuntimeException
     */
    public function toEWKT(): string
    {
        throw new \RuntimeException('FeatureCollection cannot be represented as a single EWKT geometry.');
    }

    /**
     * Serialize FeatureCollection to GeoJSON.
     */
    public function toGeoJSON(): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => array_map(fn(Feature $f) => $f->toGeoJSON(), $this->features),
        ];
    }
}