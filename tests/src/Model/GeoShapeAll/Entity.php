<?php

namespace tests\Model\GeoShapeAll;

use Milanmadar\CoolioORM\Geo\Shape;

class Entity extends \Milanmadar\CoolioORM\Entity
{
//    private Shape\Point $point_geom;
//    private Shape\LineString $linestring_geom;
//    private Shape\Polygon $polygon_geom;
//    private Shape\MultiPoint $multipoint_geom;
//    private Shape\MultiLineString $multilinestring_geom;
//    private Shape\MultiPolygon $multipolygon_geom;
//    private Shape\GeometryCollection $geomcollection_geom;
//    private Shape\CircularString $circularstring_geom;
//    private Shape\CompoundCurve $compoundcurve_geom;
//    private Shape\CurvePolygon $curvepolygon_geom;
//    private Shape\MultiCurve $multicurve_geom;

    public function getPointGeom(): Shape\Point
    {
        return $this->_get('point_geom');
    }

    public function setPointGeom(Shape\Point $val): self
    {
        $this->_set('point_geom', $val);
        return $this;
    }

    public function getLinestringGeom(): Shape\LineString
    {
        return $this->_get('linestring_geom');
    }

    public function setLinestringGeom(Shape\LineString $val): self
    {
        $this->_set('linestring_geom', $val);
        return $this;
    }

    public function getMultipointGeom(): Shape\MultiPoint
    {
        return $this->_get('multipoint_geom');
    }

    public function setMultipointGeom(Shape\MultiPoint $val): self
    {
        $this->_set('multipoint_geom', $val);
        return $this;
    }

    public function getMultilinestringGeom(): Shape\MultiLineString
    {
        return $this->_get('multilinestring_geom');
    }

    public function setMultilinestringGeom(Shape\MultiLineString $val): self
    {
        $this->_set('multilinestring_geom', $val);
        return $this;
    }

    public function getMultipolygonGeom(): Shape\MultiPolygon
    {
        return $this->_get('multipolygon_geom');
    }

    public function setMultipolygonGeom(Shape\MultiPolygon $val): self
    {
        $this->_set('multipolygon_geom', $val);
        return $this;
    }

    public function getGeomcollectionGeom(): Shape\GeometryCollection
    {
        return $this->_get('geomcollection_geom');
    }

    public function setGeomcollectionGeom(Shape\GeometryCollection $val): self
    {
        $this->_set('multipolygon_geom', $val);
        return $this;
    }

    public function getCompoundcurveGeom(): Shape\CompoundCurve
    {
        return $this->_get('compoundcurve_geom');
    }

    public function setCompoundcurveGeom(Shape\CompoundCurve $val): self
    {
        $this->_set('compoundcurve_geom', $val);
        return $this;
    }

    public function getCurvepolygonGeom(): Shape\CurvePolygon
    {
        return $this->_get('curvedpolygon_geom');
    }

    public function setCurvepolygonGeom(Shape\CurvePolygon $val): self
    {
        $this->_set('curvedpolygon_geom', $val);
        return $this;
    }

    public function getMulticurveGeom(): Shape\MultiCurve
    {
        return $this->_get('multicurve_geom');
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