<?php

namespace tests\Model\GeoShapeZMAll;

use Milanmadar\CoolioORM\Geo\ShapeZM;

class Entity extends \Milanmadar\CoolioORM\Entity
{
    public function getPointZMGeom(): ShapeZM\PointZM
    {
        return $this->_get('pointzm_geom');
    }

    public function setPointZMGeom(ShapeZM\PointZM $val): self
    {
        $this->_set('pointzm_geom', $val);
        return $this;
    }

    public function getLineStringZMGeom(): ShapeZM\LineStringZM
    {
        return $this->_get('linestringzm_geom');
    }

    public function setLineStringZMGeom(ShapeZM\LineStringZM $val): self
    {
        $this->_set('linestringzm_geom', $val);
        return $this;
    }

    public function getPolygonZMGeom(): ShapeZM\PolygonZM
    {
        return $this->_get('polygonzm_geom');
    }

    public function setPolygonZMGeom(ShapeZM\PolygonZM $val): self
    {
        $this->_set('polygonzm_geom', $val);
        return $this;
    }

    public function getMultipointZMGeom(): ShapeZM\MultipointZM
    {
        return $this->_get('multipointzm_geom');
    }

    public function setMultipointZMGeom(ShapeZM\MultipointZM $val): self
    {
        $this->_set('multipointzm_geom', $val);
        return $this;
    }

    public function getMultilinestringZMGeom(): ShapeZM\MultiLineStringZM
    {
        return $this->_get('multilinestringzm_geom');
    }

    public function setMultilinestringZMGeom(ShapeZM\MultiLineStringZM $val): self
    {
        $this->_set('multilinestringzm_geom', $val);
        return $this;
    }

    public function getMultipolygonZMGeom(): ShapeZM\MultiPolygonZM
    {
        return $this->_get('multipolygonzm_geom');
    }

    public function setMultipolygonZMGeom(ShapeZM\MultiPolygonZM $val): self
    {
        $this->_set('multipolygonzm_geom', $val);
        return $this;
    }

    public function getGeomcollectionZMGeom(): ShapeZM\GeometryCollectionZM
    {
        return $this->_get('geomcollectionzm_geom');
    }

    public function setGeomcollectionZMGeom(ShapeZM\GeometryCollectionZM $val): self
    {
        $this->_set('geomcollectionzm_geom', $val);
        return $this;
    }

    public function setCircularStringZMGeom(ShapeZM\CircularStringZM $val): self
    {
        $this->_set('circularstringzm_geom', $val);
        return $this;
    }

    public function getCircularStringZMGeom(): ShapeZM\CircularStringZM
    {
        return $this->_get('circularstringzm_geom');
    }

    public function getCompoundcurveZMGeom(): ShapeZM\CompoundCurveZM
    {
        return $this->_get('compoundcurvezm_geom');
    }

    public function setCompoundcurveZMGeom(ShapeZM\CompoundCurveZM $val): self
    {
        $this->_set('compoundcurvezm_geom', $val);
        return $this;
    }

    public function getCurvepolygonZMGeom(): ShapeZM\CurvePolygonZM
    {
        return $this->_get('curvepolygonzm_geom');
    }

    public function setCurvepolygonZMGeom(ShapeZM\CurvePolygonZM $val): self
    {
        $this->_set('curvepolygonzm_geom', $val);
        return $this;
    }

    public function getMulticurveZMGeom(): ShapeZM\MultiCurveZM
    {
        return $this->_get('multicurvezm_geom');
    }

    public function setMulticurveZMGeom(ShapeZM\MultiCurveZM $val): self
    {
        $this->_set('multicurvezm_geom', $val);
        return $this;
    }

}