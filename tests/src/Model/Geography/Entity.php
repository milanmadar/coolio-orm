<?php

namespace tests\Model\Geography;

use Milanmadar\CoolioORM;
use Milanmadar\CoolioORM\Geo\Shape2D;

class Entity extends CoolioORM\Entity
{
    /**
     * 
     * @param Shape2D\Point|null $val
     * @return $this
     */
    public function setPointGeom(?Shape2D\Point $val): self
    {
        $this->_set('point_geom', $val);
        return $this;
    }

    /**
     * 
     * @return Shape2D\Point|null
     */
    public function getPointGeom(): ?Shape2D\Point
    {
        return $this->_get('point_geom');
    }

    /**
     * 
     * @param Shape2D\LineString|null $val
     * @return $this
     */
    public function setLinestringGeom(?Shape2D\LineString $val): self
    {
        $this->_set('linestring_geom', $val);
        return $this;
    }

    /**
     * 
     * @return Shape2D\LineString|null
     */
    public function getLinestringGeom(): ?Shape2D\LineString
    {
        return $this->_get('linestring_geom');
    }

}