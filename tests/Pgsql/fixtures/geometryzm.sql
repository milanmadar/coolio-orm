CREATE EXTENSION IF NOT EXISTS postgis;

DROP TABLE IF EXISTS geometryzm_test;

CREATE TABLE geometryzm_test (
  id SERIAL PRIMARY KEY,
  pointzm_geom             geometry(PointZM, 4326)
--   ,
--   linestringzm_geom        geometry(LineStringZM, 4326),
--   polygonzm_geom           geometry(PolygonZM, 4326),
--   multipointzm_geom        geometry(MultiPointZM, 4326),
--   multilinestringzm_geom   geometry(MultiLineStringZM, 4326),
--   multipolygonzm_geom      geometry(MultiPolygonZM, 4326),
--   geomcollectionzm_geom    geometry(GeometryCollectionZM, 4326),
--
--     -- curve types
--   circularstringzm_geom    geometry(CircularStringZM, 4326),
--   compoundcurvezm_geom     geometry(CompoundCurveZM, 4326),
--   curvedpolygonzm_geom      geometry(CurvePolygonZM, 4326),
--   multicurvezm_geom        geometry(MultiCurveZM, 4326)
);

INSERT INTO geometryzm_test (
    pointzm_geom
--     ,
--     linestringzm_geom,
--     polygonzm_geom,
--     multipointzm_geom,
--     multilinestringzm_geom,
--     multipolygonzm_geom,
--     geomcollectionzm_geom,
--     circularstringzm_geom,
--     compoundcurvezm_geom,
--     curvedpolygonzm_geom,
--     multicurvezm_geom
) VALUES (
     ST_GeomFromEWKT('SRID=4326;POINT ZM(1 2 3 4)')
--              ,
--      ST_GeomFromEWKT('SRID=4326;LINESTRING ZM(0 0 0 100, 1 1 1 100, 2 2 2 100)'),
--      ST_GeomFromEWKT('SRID=4326;POLYGON ZM((0 0 0 100, 0 5 0 100,5 5 0 100,5 0 0 100,0 0 0 100))'),
--      ST_GeomFromEWKT('SRID=4326;MULTIPOINT ZM((1 1 1 100),(2 2 2 100),(3 3 3 100))'),
--      ST_GeomFromEWKT('SRID=4326;MULTILINESTRING ZM((0 0 0 100,1 1 1 100),(2 2 2 100,3 3 3 100))'),
--      ST_GeomFromEWKT('SRID=4326;MULTIPOLYGON ZM(((0 0 0 100,0 3 0 100,3 3 0 100,3 0 0 100,0 0 0 100)),((4 4 4 100,4 6 4 100,6 6 4 100,6 4 4 100,4 4 4 100)))'),
--      ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTION ZM(POINT ZM(1 2 3 100),LINESTRING ZM(0 0 0 100,1 1 1 100))'),
--      ST_GeomFromEWKT('SRID=4326;CIRCULARSTRING ZM(0 0 0 100,1 1 1 100,2 0 0 100)'),
--      ST_GeomFromEWKT('SRID=4326;COMPOUNDCURVE ZM((0 0 0 100,1 1 1 100),CIRCULARSTRING ZM(1 1 1 100,2 2 2 100,3 1 1 100))'),
--      ST_GeomFromEWKT('SRID=4326;CURVEPOLYGON ZM(CIRCULARSTRING ZM(0 0 0 100,2 2 2 100,4 0 0 100,5 2 2 100,0 0 0 100))'),
--      ST_GeomFromEWKT('SRID=4326;MULTICURVE ZM(CIRCULARSTRING ZM(0 0 0 100,1 1 1 100,2 0 0 100),LINESTRING ZM(2 0 0 100,3 1 1 100))')
 );