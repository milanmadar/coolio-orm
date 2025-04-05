DROP TABLE IF EXISTS geometry_test CASCADE;

CREATE TABLE IF NOT EXISTS geometry_test
(
    id SERIAL PRIMARY KEY,
    polygon_geom geometry(Polygon,4326),
    circularstring_geom geometry(CIRCULARSTRING, 4326)
);

INSERT INTO geometry_test (polygon_geom, circularstring_geom) VALUES
    (
     ST_GeomFromText('POLYGON((0 0, 1 1, 1 0, 0 0))', 4326),
     ST_GeomFromText('CIRCULARSTRING(0 0, 1 1, 2 0)', 4326)
    )
;



