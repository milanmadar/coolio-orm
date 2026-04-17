CREATE EXTENSION IF NOT EXISTS postgis;

DROP TABLE IF EXISTS geo_join_a_test;
CREATE TABLE geo_join_a_test (
    id SERIAL PRIMARY KEY,
    point_geom geometry(Point, 4326),
    fld_varchar VARCHAR(45) DEFAULT 'hello',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL
);

DROP TABLE IF EXISTS geo_join_b_test;
CREATE TABLE geo_join_b_test (
    id SERIAL PRIMARY KEY,
    a_id INT NOT NULL,
    fld_notinother int default 1,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL
);

INSERT INTO geo_join_a_test (point_geom, fld_varchar) VALUES
    (ST_GeomFromEWKT('SRID=4326;POINT(1 2)'), 'apple'),
    (ST_GeomFromEWKT('SRID=4326;POINT(3 4)'), 'apple'),
    (ST_GeomFromEWKT('SRID=4326;POINT(5 6)'), 'banana');

INSERT INTO geo_join_b_test (a_id, fld_notinother) VALUES
    (1, 10),
    (2, 20),
    (3, 30);