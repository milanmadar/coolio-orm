<?php

namespace tests\Model\TopologyTest;

use Milanmadar\CoolioORM\Geo\Shape2D;

class Entity extends \Milanmadar\CoolioORM\Entity
{
    public function getName(): string
    {
        return $this->_get('name');
    }

    public function setName(string $val): self
    {
        $this->_set('name', $val);
        return $this;
    }

    public function getTopoGeomPoint(): Shape2D\MultiPoint
    {
        return $this->_get('topo_geom_point');
    }

    public function setTopoGeomPoint(Shape2D\MultiPoint $val): self
    {
        $this->_set('topo_geom_point', $val);
        return $this;
    }

    public function getTopoGeomLinestring(): Shape2D\MultiLineString
    {
        return $this->_get('topo_geom_linestring');
    }

    public function setTopoGeomLinestring(Shape2D\MultiLineString $val): self
    {
        $this->_set('topo_geom_linestring', $val);
        return $this;
    }

    public function getTopoGeomPolygon(): Shape2D\MultiPolygon
    {
        return $this->_get('topo_geom_polygon');
    }

    public function setTopoGeomPolygon(Shape2D\MultiPolygon $val): self
    {
        $this->_set('topo_geom_polygon', $val);
        return $this;
    }

    public function getTopoGeomGeometrycollection(): Shape2D\GeometryCollection
    {
        return $this->_get('topo_geom_collection');
    }

    public function setTopoGeomGeometrycollection(Shape2D\GeometryCollection $val): self
    {
        $this->_set('topo_geom_collection', $val);
        return $this;
    }

}