CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_topology;

DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM topology.topology WHERE name = 'topology_test_topo') THEN
    PERFORM DropTopology('topology_test_topo');
END IF;
END
$$;

SELECT CreateTopology('topology_test_topo', 4326);

DROP TABLE IF EXISTS topology_test;

CREATE TABLE public.topology_test (
      id SERIAL PRIMARY KEY,
      name VARCHAR(45) DEFAULT NULL
);

SELECT AddTopoGeometryColumn(
       'topology_test_topo', -- the topology schema
       'public',        -- the table schema
       'topology_test',  -- the table name
       'topo_geom_point',     -- the new column name
       'MULTIPOINT'     -- the type of feature (can also be POINT, POLYGON)
);

SELECT AddTopoGeometryColumn(
       'topology_test_topo', -- the topology schema
       'public',        -- the table schema
       'topology_test',  -- the table name
       'topo_geom_linestring',     -- the new column name
       'MULTILINESTRING'     -- the type of feature (can also be POINT, POLYGON)
);

SELECT AddTopoGeometryColumn(
       'topology_test_topo', -- the topology schema
       'public',        -- the table schema
       'topology_test',  -- the table name
       'topo_geom_polygon',     -- the new column name
       'MULTIPOLYGON'     -- the type of feature (can also be POINT, POLYGON)
);

SELECT AddTopoGeometryColumn(
       'topology_test_topo', -- the topology schema
       'public',        -- the table schema
       'topology_test',  -- the table name
       'topo_geom_collection',     -- the new column name
       'COLLECTION'     -- the type of feature (can also be POINT, POLYGON)
);

INSERT INTO public.topology_test (
    name,
    topo_geom_point,
    topo_geom_linestring,
    topo_geom_polygon,
    topo_geom_collection
) VALUES (
 'Main Street',
 toTopoGeom(
     ST_GeomFromEWKT('SRID=4326;MULTIPOINT(1 2)'), -- your raw geometry
     'topology_test_topo',                -- topology schema
     1,                                   -- topology layer id (typically 1 if only 1 layer)
     0.001                                -- tolerance for snapping and validation
 ),
 toTopoGeom(
     ST_GeomFromEWKT('SRID=4326;MULTILINESTRING((1 2, 3 4))'), -- your raw geometry
     'topology_test_topo',                          -- topology schema
     2,                                             -- topology layer id (typically 1 if only 1 layer)
     0.001                                          -- tolerance for snapping and validation
 ),
 toTopoGeom(
     ST_GeomFromEWKT('SRID=4326;MULTIPOLYGON(((0 0,0 5,5 5,5 0,0 0),(1 1,1 2,2 2,2 1,1 1)))'), -- your raw geometry
     'topology_test_topo',                           -- topology schema
     3,                                              -- topology layer id (typically 1 if only 1 layer)
     0.001                                           -- tolerance for snapping and validation
 ),
 toTopoGeom(
     ST_GeomFromEWKT('SRID=4326;GEOMETRYCOLLECTION(POINT(1 1),LINESTRING(2 2,3 3,4 4))'), -- your raw geometry
     'topology_test_topo',                                -- topology schema
     4,                                              -- topology layer id (typically 1 if only 1 layer)
     0.001                                           -- tolerance for snapping and validation
)
);