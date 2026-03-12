DROP TABLE IF EXISTS geometryz_general;
CREATE TABLE geometryz_general (
                                   id SERIAL PRIMARY KEY,
                                   geom_wgs        geometry(GeometryZ, 4326),
                                   geom_regional   geometry(GeometryZ),
                                   srid_regional INTEGER NOT NULL,
                                   length_meters DOUBLE PRECISION NOT NULL,
                                   elevation_meters DOUBLE PRECISION NOT NULL
);
CREATE INDEX idx_geometryz_general_regional ON geometryz_general USING GIST (geom_regional);
CREATE INDEX idx_geometryz_general_srid ON geometryz_general (srid_regional);
COMMENT ON column geometryz_general.geom_wgs is 'SRID=4326, WGS 84';
COMMENT ON column geometryz_general.geom_regional is 'SRID depends on the region, eg: 32633';

-- INSERT Point
WITH parsed_geom AS (
    SELECT ST_Transform(ST_GeomFromEWKT('SRID=4326;POINT Z(12.6939 47.0744 3798)'), 32633) as g
)
INSERT INTO geometryz_general (
    geom_wgs,
    geom_regional,
    srid_regional,
    length_meters,
    elevation_meters
)
SELECT
    ST_GeomFromEWKT('SRID=4326;POINT Z(12.6939 47.0744 3798)'),
    g,
    32633,
    ST_3DLength(g),
    ST_ZMax(g) - ST_ZMin(g)
FROM parsed_geom;

-- INSERT LineString
WITH parsed_geom AS (
    SELECT ST_Transform(ST_GeomFromEWKT('SRID=4326;LINESTRING Z(12.6939 47.0744 3798, 12.6980 47.0800 3825, 12.7020 47.0840 3848)'), 32633) as g
)
INSERT INTO geometryz_general (
    geom_wgs,
    geom_regional,
    srid_regional,
    length_meters,
    elevation_meters
)
SELECT
    ST_GeomFromEWKT('SRID=4326;LINESTRING Z(12.6939 47.0744 3798, 12.6980 47.0800 3825, 12.7020 47.0840 3848)'),
    g,
    32633,
    ST_3DLength(g),
    ST_ZMax(g) - ST_ZMin(g)
FROM parsed_geom;