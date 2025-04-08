<?php

namespace tests\Model\GeoShapeAll;

use Milanmadar\CoolioORM\Geo\Shape2D;

class Entity extends \Milanmadar\CoolioORM\Entity
{
    public function getPointGeom(): Shape2D\Point
    {
        return $this->_get('point_geom');
    }

    public function setPointGeom(Shape2D\Point $val): self
    {
        $this->_set('point_geom', $val);
        return $this;
    }

    public function getLinestringGeom(): Shape2D\LineString
    {
        return $this->_get('linestring_geom');
    }

    public function setLinestringGeom(Shape2D\LineString $val): self
    {
        $this->_set('linestring_geom', $val);
        return $this;
    }

    public function getMultipointGeom(): Shape2D\MultiPoint
    {
        return $this->_get('multipoint_geom');
    }

    public function setMultipointGeom(Shape2D\MultiPoint $val): self
    {
        $this->_set('multipoint_geom', $val);
        return $this;
    }

    public function getMultilinestringGeom(): Shape2D\MultiLineString
    {
        return $this->_get('multilinestring_geom');
    }

    public function setMultilinestringGeom(Shape2D\MultiLineString $val): self
    {
        $this->_set('multilinestring_geom', $val);
        return $this;
    }

    public function getMultipolygonGeom(): Shape2D\MultiPolygon
    {
        return $this->_get('multipolygon_geom');
    }

    public function setMultipolygonGeom(Shape2D\MultiPolygon $val): self
    {
        $this->_set('multipolygon_geom', $val);
        return $this;
    }

    public function getGeomcollectionGeom(): Shape2D\GeometryCollection
    {
        return $this->_get('geomcollection_geom');
    }

    public function setGeomcollectionGeom(Shape2D\GeometryCollection $val): self
    {
        $this->_set('geomcollection_geom', $val);
        return $this;
    }

    public function getCompoundcurveGeom(): Shape2D\CompoundCurve
    {
        return $this->_get('compoundcurve_geom');
    }

    public function setCompoundcurveGeom(Shape2D\CompoundCurve $val): self
    {
        $this->_set('compoundcurve_geom', $val);
        return $this;
    }

    public function getCurvepolygonGeom(): Shape2D\CurvePolygon
    {
        return $this->_get('curvedpolygon_geom');
    }

    public function setCurvepolygonGeom(Shape2D\CurvePolygon $val): self
    {
        $this->_set('curvedpolygon_geom', $val);
        return $this;
    }

    public function getMulticurveGeom(): Shape2D\MultiCurve
    {
        return $this->_get('multicurve_geom');
    }

    public function setMulticurveGeom(Shape2D\MultiCurve $val): self
    {
        $this->_set('multicurve_geom', $val);
        return $this;
    }

    /**
     * 
     * @param Shape2D\Polygon $val
     * @return $this
     */
    public function setPolygonGeom(Shape2D\Polygon $val): self
    {
        $this->_set('polygon_geom', $val);
        return $this;
    }

    /**
     * 
     * @return Shape2D\Polygon
     */
    public function getPolygonGeom(): Shape2D\Polygon
    {
        return $this->_get('polygon_geom');
    }

    /**
     *
     * @param Shape2D\CircularString $val
     * @return $this
     */
    public function setCircularStringGeom(Shape2D\CircularString $val): self
    {
        $this->_set('circularstring_geom', $val);
        return $this;
    }

    /**
     *
     * @return Shape2D\CircularString
     */
    public function getCircularStringGeom(): Shape2D\CircularString
    {
        return $this->_get('circularstring_geom');
    }

}