DROP FUNCTION IF EXISTS orm_test_function;
CREATE OR REPLACE FUNCTION orm_test_function(
    p_int    INT,
    p_text   TEXT,
    p_bool   BOOLEAN,
    p_float  DOUBLE PRECISION,
    p_geom_point GEOMETRY(Point, 4326)
)
RETURNS TABLE (
    out_int   INT,
    out_text  TEXT,
    out_bool  BOOLEAN,
    out_float DOUBLE PRECISION,
    out_geom_point TEXT
)
LANGUAGE plpgsql
AS $$
BEGIN
    out_int   := p_int + 1;
    out_text  := p_text || 'OK';  -- append 'OK'
    out_bool  := NOT p_bool;
    out_float := p_float * 2.0;

    out_geom_point := ST_AsEWKT( ST_SetSRID(
        ST_MakePoint(
            ST_X(p_geom_point) + 1,
            ST_Y(p_geom_point) + 1
        ),
        4326
    ) );

    RETURN NEXT;
END;
$$;

--

DROP FUNCTION IF EXISTS orm_test_function_retInt;
CREATE OR REPLACE FUNCTION orm_test_function_retInt(
    p_int        INT,
    p_text       TEXT,
    p_bool       BOOLEAN,
    p_float      DOUBLE PRECISION,
    p_geom_point GEOMETRY(Point, 4326)
)
RETURNS INT AS $$
BEGIN
    RETURN 100;
END;
$$ LANGUAGE plpgsql;