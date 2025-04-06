CREATE EXTENSION IF NOT EXISTS postgis;

DROP TABLE IF EXISTS geometry_test;

CREATE TABLE geometry_test (
   id SERIAL PRIMARY KEY,
   point_geom             geometry(Point, 4326),
   linestring_geom        geometry(LineString, 4326),
   polygon_geom           geometry(Polygon, 4326),
   multipoint_geom        geometry(MultiPoint, 4326),
   multilinestring_geom   geometry(MultiLineString, 4326),
   multipolygon_geom      geometry(MultiPolygon, 4326),
   geomcollection_geom    geometry(GeometryCollection, 4326),

    -- curve types
   circularstring_geom geometry(CIRCULARSTRING, 4326),
   compoundcurve_geom geometry(COMPOUNDCURVE, 4326),
   curvedpolygon_geom geometry(CURVEPOLYGON, 4326),
   multicurve_geom geometry(MULTICURVE, 4326)
);

COMMENT ON column geometry_test.point_geom is 'Just any Point to test the Geo ORM';
COMMENT ON column geometry_test.point_geom is 'Just any LineString to test the Geo ORM';
COMMENT ON column geometry_test.point_geom is 'Just any Polygon to test the Geo ORM';
COMMENT ON column geometry_test.point_geom is 'Just any MultiPoint to test the Geo ORM';
COMMENT ON column geometry_test.point_geom is 'Just any MultiLineString to test the Geo ORM';
COMMENT ON column geometry_test.point_geom is 'Just any MultiPolygon to test the Geo ORM';
COMMENT ON column geometry_test.point_geom is 'Just any GeometryCollection to test the Geo ORM';
COMMENT ON column geometry_test.point_geom is 'Just any CircularString to test the Geo ORM';
COMMENT ON column geometry_test.point_geom is 'Just any CompoundCurve to test the Geo ORM';
COMMENT ON column geometry_test.point_geom is 'Just any CurvePolygon to test the Geo ORM';
COMMENT ON column geometry_test.point_geom is 'Just any MultiCurve to test the Geo ORM';

INSERT INTO geometry_test (
    point_geom,
    linestring_geom,
    polygon_geom,
    multipoint_geom,
    multilinestring_geom,
    multipolygon_geom,
    geomcollection_geom,

    -- curve types
    circularstring_geom,
    compoundcurve_geom,
    curvedpolygon_geom,
    multicurve_geom
)
VALUES (
   ST_GeomFromEWKT('SRID=4326;POINT(1 2)'),
   ST_GeomFromEWKT('SRID=4326;LINESTRING(0 0, 3 3, 5 1)'),
   ST_GeomFromEWKT('SRID=4326;POLYGON((0 0, 4 0, 4 4, 0 4, 0 0))'),
   ST_GeomFromEWKT('SRID=4326;MULTIPOINT((1 1), (2 2), (-1 -1))'),
   ST_GeomFromEWKT('SRID=4326;MULTILINESTRING((0 0, 1 1), (2 2, 3 3))'),
   ST_GeomFromEWKT('SRID=4326;MULTIPOLYGON(((0 0, 2 0, 2 2, 0 2, 0 0)), ((3 3, 5 3, 5 5, 3 5, 3 3)))'),
   ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTION(POINT(0 0), LINESTRING(1 1, 2 2), POLYGON((0 0, 1 0, 1 1, 0 1, 0 0)))'),

   -- curve types
   ST_GeomFromText('CIRCULARSTRING(0 0, 1 1, 2 0)', 4326),
   ST_GeomFromText('COMPOUNDCURVE((2 0, 3 1), CIRCULARSTRING(3 1, 4 2, 5 1))', 4326),
   ST_GeomFromText('CURVEPOLYGON(CIRCULARSTRING(0 0, 4 0, 4 4, 0 4, 0 0), (1 1, 3 3, 3 1, 1 1))', 4326),
   ST_GeomFromText(
           'MULTICURVE(
               CIRCULARSTRING(0 0, 1 2, 2 0),
               COMPOUNDCURVE((2 0, 3 1), CIRCULARSTRING(3 1, 4 2, 5 1))
           )',
           4326
   )
);
