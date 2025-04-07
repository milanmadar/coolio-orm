<?php

namespace tests\Model\GeoShapeZAll;

use Milanmadar\CoolioORM\Geo\ShapeZ;

class Entity extends \Milanmadar\CoolioORM\Entity
{
    public function getPointZGeom(): ShapeZ\PointZ
    {
        return $this->_get('pointz_geom');
    }

    public function setPointZGeom(ShapeZ\PointZ $val): self
    {
        $this->_set('pointz_geom', $val);
        return $this;
    }

    public function getLineStringZGeom(): ShapeZ\LineStringZ
    {
        return $this->_get('linestringz_geom');
    }

    public function setLineStringZGeom(ShapeZ\LineStringZ $val): self
    {
        $this->_set('linestringz_geom', $val);
        return $this;
    }

    public function getPolygonZGeom(): ShapeZ\PolygonZ
    {
        return $this->_get('polygonz_geom');
    }

    public function setPolygonZGeom(ShapeZ\PolygonZ $val): self
    {
        $this->_set('polygonz_geom', $val);
        return $this;
    }

    public function getMultipointZGeom(): ShapeZ\MultipointZ
    {
        return $this->_get('multipointz_geom');
    }

    public function setMultipointZGeom(ShapeZ\MultipointZ $val): self
    {
        $this->_set('multipointz_geom', $val);
        return $this;
    }

    public function getMultilinestringZGeom(): ShapeZ\MultiLineStringZ
    {
        return $this->_get('multilinestringz_geom');
    }

    public function setMultilinestringZGeom(ShapeZ\MultiLineStringZ $val): self
    {
        $this->_set('multilinestringz_geom', $val);
        return $this;
    }

    public function getMultipolygonZGeom(): ShapeZ\MultiPolygonZ
    {
        return $this->_get('multipolygonz_geom');
    }

    public function setMultipolygonZGeom(ShapeZ\MultiPolygonZ $val): self
    {
        $this->_set('multipolygonz_geom', $val);
        return $this;
    }

    public function getGeomcollectionZGeom(): ShapeZ\GeometryCollectionZ
    {
        return $this->_get('geomcollectionz_geom');
    }

    public function setGeomcollectionZGeom(ShapeZ\GeometryCollectionZ $val): self
    {
        $this->_set('geomcollectionz_geom', $val);
        return $this;
    }

    public function setCircularStringZGeom(ShapeZ\CircularStringZ $val): self
    {
        $this->_set('circularstringz_geom', $val);
        return $this;
    }

    public function getCircularStringZGeom(): ShapeZ\CircularStringZ
    {
        return $this->_get('circularstringz_geom');
    }

}