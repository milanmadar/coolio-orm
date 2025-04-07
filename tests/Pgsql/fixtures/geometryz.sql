CREATE EXTENSION IF NOT EXISTS postgis;

DROP TABLE IF EXISTS geometryz_test;

CREATE TABLE geometryz_test (
  id SERIAL PRIMARY KEY,
  pointz_geom             geometry(PointZ, 4326),
  linestringz_geom        geometry(LineStringZ, 4326)
);

INSERT INTO geometryz_test (
    pointz_geom,
    linestringz_geom
) VALUES (
     ST_GeomFromEWKT('SRID=4326;POINT Z(1 2 3)'),
     ST_GeomFromEWKT('SRID=4326;LINESTRING Z(0 0 0, 1 1 1, 2 2 2)')
 );