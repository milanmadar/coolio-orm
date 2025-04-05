<?php

namespace tests\Model\GeoShapeAll;

use Milanmadar\CoolioORM\Geo\Shape;

class Entity extends \Milanmadar\CoolioORM\Entity
{
    /**
     * 
     * @param Shape\Polygon $val
     * @return $this
     */
    public function setPolygonGeom(Shape\Polygon $val): self
    {
        $this->_set('polygon_geom', $val);
        return $this;
    }

    /**
     * 
     * @return Shape\Polygon
     */
    public function getPolygonGeom(): Shape\Polygon
    {
        return $this->_get('polygon_geom');
    }

    /**
     *
     * @param Shape\CircularString $val
     * @return $this
     */
    public function setCircularStringGeom(Shape\CircularString $val): self
    {
        $this->_set('circularstring_geom', $val);
        return $this;
    }

    /**
     *
     * @return Shape\CircularString
     */
    public function getCircularStringGeom(): Shape\CircularString
    {
        return $this->_get('circularstring_geom');
    }

}