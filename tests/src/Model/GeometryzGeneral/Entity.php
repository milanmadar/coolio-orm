<?php

namespace tests\Model\GeometryzGeneral;

use Milanmadar\CoolioORM;
use Milanmadar\CoolioORM\Geo\ShapeZ;

class Entity extends CoolioORM\Entity
{
    /**
     * SRID=4326, WGS 84
     * @param ShapeZ\AbstractShapeZ|null $val
     * @return $this
     */
    public function setGeomWgs(?ShapeZ\AbstractShapeZ $val): self
    {
        $this->_set('geom_wgs', $val);
        return $this;
    }

    /**
     * SRID=4326, WGS 84
     * @return ShapeZ\AbstractShapeZ|null
     */
    public function getGeomWgs(): ?ShapeZ\AbstractShapeZ
    {
        return $this->_get('geom_wgs');
    }

    /**
     * SRID depends on the region, eg: 32633
     * @param ShapeZ\AbstractShapeZ|null $val
     * @return $this
     */
    public function setGeomRegional(?ShapeZ\AbstractShapeZ $val): self
    {
        $this->_set('geom_regional', $val);
        return $this;
    }

    /**
     * SRID depends on the region, eg: 32633
     * @return ShapeZ\AbstractShapeZ|null
     */
    public function getGeomRegional(): ?ShapeZ\AbstractShapeZ
    {
        return $this->_get('geom_regional');
    }

    /**
     * 
     * @param int $val
     * @return $this
     */
    public function setSridRegional(int $val): self
    {
        $this->_set('srid_regional', $val);
        return $this;
    }

    /**
     * 
     * @return int
     */
    public function getSridRegional(): int
    {
        return $this->_get('srid_regional');
    }

    /**
     * 
     * @param float $val
     * @return $this
     */
    public function setLengthMeters(float $val): self
    {
        $this->_set('length_meters', $val);
        return $this;
    }

    /**
     * 
     * @return float
     */
    public function getLengthMeters(): float
    {
        return $this->_get('length_meters');
    }

    /**
     * 
     * @param float $val
     * @return $this
     */
    public function setElevationMeters(float $val): self
    {
        $this->_set('elevation_meters', $val);
        return $this;
    }

    /**
     * 
     * @return float
     */
    public function getElevationMeters(): float
    {
        return $this->_get('elevation_meters');
    }

}