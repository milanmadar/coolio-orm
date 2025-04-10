<?php

namespace tests\Model\TopologyTest;

use Milanmadar\CoolioORM;
use Milanmadar\CoolioORM\Geo\Shape2D;

class Entity extends CoolioORM\Entity
{
    /**
     *
     * @param string|null $val
     * @return $this
     */
    public function setName(?string $val): self
    {
        $this->_set('name', $val);
        return $this;
    }

    /**
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->_get('name');
    }

    /**
     *
     * @param Shape2D\MultiPoint|null $val
     * @return $this
     */
    public function setTopoGeomPoint(?Shape2D\MultiPoint $val): self
    {
        $this->_set('topo_geom_point', $val);
        return $this;
    }

    /**
     *
     * @return Shape2D\MultiPoint|null
     */
    public function getTopoGeomPoint(): ?Shape2D\MultiPoint
    {
        return $this->_get('topo_geom_point');
    }

    /**
     *
     * @param Shape2D\MultiLineString|null $val
     * @return $this
     */
    public function setTopoGeomLinestring(?Shape2D\MultiLineString $val): self
    {
        $this->_set('topo_geom_linestring', $val);
        return $this;
    }

    /**
     *
     * @return Shape2D\MultiLineString|null
     */
    public function getTopoGeomLinestring(): ?Shape2D\MultiLineString
    {
        return $this->_get('topo_geom_linestring');
    }

    /**
     *
     * @param Shape2D\MultiPolygon|null $val
     * @return $this
     */
    public function setTopoGeomPolygon(?Shape2D\MultiPolygon $val): self
    {
        $this->_set('topo_geom_polygon', $val);
        return $this;
    }

    /**
     *
     * @return Shape2D\MultiPolygon|null
     */
    public function getTopoGeomPolygon(): ?Shape2D\MultiPolygon
    {
        return $this->_get('topo_geom_polygon');
    }

    /**
     *
     * @param Shape2D\GeometryCollection|null $val
     * @return $this
     */
    public function setTopoGeomCollection(?Shape2D\GeometryCollection $val): self
    {
        $this->_set('topo_geom_collection', $val);
        return $this;
    }

    /**
     *
     * @return Shape2D\GeometryCollection|null
     */
    public function getTopoGeomCollection(): ?Shape2D\GeometryCollection
    {
        return $this->_get('topo_geom_collection');
    }

}