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

    public function getLineStringZMGeom(): ShapeZM\LineStringZ
    {
        return $this->_get('linestringzm_geom');
    }

    public function setLineStringZMGeom(ShapeZM\LineStringZ $val): self
    {
        $this->_set('linestringzm_geom', $val);
        return $this;
    }

    public function getPolygonZMGeom(): ShapeZM\PolygonZ
    {
        return $this->_get('polygonzm_geom');
    }

    public function setPolygonZMGeom(ShapeZM\PolygonZ $val): self
    {
        $this->_set('polygonzm_geom', $val);
        return $this;
    }

    public function getMultipointZMGeom(): ShapeZM\MultipointZ
    {
        return $this->_get('multipointzm_geom');
    }

    public function setMultipointZMGeom(ShapeZM\MultipointZ $val): self
    {
        $this->_set('multipointzm_geom', $val);
        return $this;
    }

    public function getMultilinestringZMGeom(): ShapeZM\MultiLineStringZ
    {
        return $this->_get('multilinestringzm_geom');
    }

    public function setMultilinestringZMGeom(ShapeZM\MultiLineStringZ $val): self
    {
        $this->_set('multilinestringzm_geom', $val);
        return $this;
    }

    public function getMultipolygonZMGeom(): ShapeZM\MultiPolygonZ
    {
        return $this->_get('multipolygonzm_geom');
    }

    public function setMultipolygonZMGeom(ShapeZM\MultiPolygonZ $val): self
    {
        $this->_set('multipolygonzm_geom', $val);
        return $this;
    }

    public function getGeomcollectionZMGeom(): ShapeZM\GeometryCollectionZ
    {
        return $this->_get('geomcollectionzm_geom');
    }

    public function setGeomcollectionZMGeom(ShapeZM\GeometryCollectionZ $val): self
    {
        $this->_set('geomcollectionzm_geom', $val);
        return $this;
    }

    public function setCircularStringZMGeom(ShapeZM\CircularStringZ $val): self
    {
        $this->_set('circularstringzm_geom', $val);
        return $this;
    }

    public function getCircularStringZMGeom(): ShapeZM\CircularStringZ
    {
        return $this->_get('circularstringzm_geom');
    }

    public function getCompoundcurveZMGeom(): ShapeZM\CompoundCurveZ
    {
        return $this->_get('compoundcurvezm_geom');
    }

    public function setCompoundcurveZMGeom(ShapeZM\CompoundCurveZ $val): self
    {
        $this->_set('compoundcurvezm_geom', $val);
        return $this;
    }

    public function getCurvepolygonZMGeom(): ShapeZM\CurvePolygonZ
    {
        return $this->_get('curvedpolygonzm_geom');
    }

    public function setCurvepolygonZMGeom(ShapeZM\CurvePolygonZ $val): self
    {
        $this->_set('curvedpolygonzm_geom', $val);
        return $this;
    }

    public function setMulticurveGeom(Shape\MultiCurve $val): self
    {
        $this->_set('multicurve_geom', $val);
        return $this;
    }

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

    public function getMulticurveZMGeom(): ShapeZM\MultiCurveZ
    {
        return $this->_get('multicurvezm_geom');
    }

    public function setMulticurveZMGeom(ShapeZM\MultiCurveZ $val): self
    {
        $this->_set('multicurvezm_geom', $val);
        return $this;
    }

}