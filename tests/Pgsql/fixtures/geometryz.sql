CREATE EXTENSION IF NOT EXISTS postgis;

DROP TABLE IF EXISTS geometryz_test;

CREATE TABLE geometryz_test (
  id SERIAL PRIMARY KEY,
  pointz_geom             geometry(PointZ, 4326),
  linestringz_geom        geometry(LineStringZ, 4326),
  polygonz_geom           geometry(PolygonZ, 4326),
  multipointz_geom        geometry(MultiPointZ, 4326),
  multilinestringz_geom   geometry(MultiLineStringZ, 4326),
  multipolygonz_geom      geometry(MultiPolygonZ, 4326),
  geomcollectionz_geom    geometry(GeometryCollectionZ, 4326),

    -- curve types
  circularstringz_geom    geometry(CircularStringZ, 4326),
  compoundcurvez_geom     geometry(CompoundCurveZ, 4326),
  curvedpolygonz_geom      geometry(CurvePolygonZ, 4326),
  multicurvez_geom        geometry(MultiCurveZ, 4326)
);

INSERT INTO geometryz_test (
    pointz_geom,
    linestringz_geom,
    polygonz_geom,
    multipointz_geom,
    multilinestringz_geom,
    multipolygonz_geom,
    geomcollectionz_geom,
    circularstringz_geom,
    compoundcurvez_geom,
    curvedpolygonz_geom,
    multicurvez_geom
) VALUES (
     ST_GeomFromEWKT('SRID=4326;POINT Z(1 2 3)'),
     ST_GeomFromEWKT('SRID=4326;LINESTRING Z(0 0 0, 1 1 1, 2 2 2)'),
     ST_GeomFromEWKT('SRID=4326;POLYGON Z((0 0 0,0 5 0,5 5 0,5 0 0,0 0 0))'),
     ST_GeomFromEWKT('SRID=4326;MULTIPOINT Z((1 1 1),(2 2 2),(3 3 3))'),
     ST_GeomFromEWKT('SRID=4326;MULTILINESTRING Z((0 0 0,1 1 1),(2 2 2,3 3 3))'),
     ST_GeomFromEWKT('SRID=4326;MULTIPOLYGON Z(((0 0 0,0 3 0,3 3 0,3 0 0,0 0 0)),((4 4 4,4 6 4,6 6 4,6 4 4,4 4 4)))'),
     ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTION Z(POINT Z(1 2 3),LINESTRING Z(0 0 0,1 1 1))'),
     ST_GeomFromEWKT('SRID=4326;CIRCULARSTRING Z(0 0 0,1 1 1,2 0 0)'),
     ST_GeomFromEWKT('SRID=4326;COMPOUNDCURVE Z((0 0 0,1 1 1),CIRCULARSTRING Z(1 1 1,2 2 2,3 1 1))'),
     ST_GeomFromEWKT('SRID=4326;CURVEPOLYGON Z(CIRCULARSTRING Z(0 0 0,2 2 2,4 0 0,5 2 2,0 0 0))'),
     ST_GeomFromEWKT('SRID=4326;MULTICURVE Z(CIRCULARSTRING Z(0 0 0,1 1 1,2 0 0),LINESTRING Z(2 0 0,3 1 1))')
 );