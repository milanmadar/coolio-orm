DROP TABLE IF EXISTS orm_third CASCADE;

CREATE TABLE orm_third (
   id SERIAL PRIMARY KEY,
   fk_to_this VARCHAR(45) NULL,
   CONSTRAINT idx_to_this UNIQUE (fk_to_this)
);

INSERT INTO orm_third (fk_to_this) VALUES
   ('third hali'),
   ('third bye');

-- -----------


DROP TABLE IF EXISTS orm_other CASCADE;
CREATE TABLE orm_other (
   id SERIAL PRIMARY KEY,
   fld_int int NOT NULL,
   title varchar(45) DEFAULT NULL
);

INSERT INTO orm_other VALUES
  (1,1,'first'),
  (2,2,'second');

SELECT setval('public.orm_other_id_seq', (SELECT max(id) FROM public.orm_other));

-- ----------------------------------

DROP TABLE IF EXISTS orm_test CASCADE;
CREATE TABLE orm_test (
  id SERIAL PRIMARY KEY,
  fld_int INT DEFAULT NULL,  -- 'an integer'
  fld_tiny_int SMALLINT DEFAULT NULL,  -- 'a tiny int'
  fld_small_int SMALLINT DEFAULT NULL,  -- 'a small int'
  fld_medium_int INTEGER DEFAULT NULL,  -- 'a medium int' (PostgreSQL has no MEDIUMINT)
  fld_float REAL DEFAULT NULL,  -- 'a floating 8,2' (MySQL FLOAT(8,2) is not needed in Postgres)
  fld_double DOUBLE PRECISION DEFAULT NULL,  -- 'a double 8,2'
  fld_decimal NUMERIC(8,2) DEFAULT 1.23,  -- 'a decimal 8,2'
  fld_char CHAR(8) DEFAULT NULL,  -- 'a char 8'
  fld_varchar VARCHAR(45) DEFAULT 'field''s "quoted" def val',  -- 'a varchar 25'
  fld_text TEXT DEFAULT NULL,  -- 'a text'
  fld_medium_text TEXT DEFAULT NULL,  -- No MEDIUMTEXT in PostgreSQL, TEXT is sufficient
  fld_json JSON DEFAULT NULL,  -- 'json data'
  fld_str_array TEXT[] DEFAULT NULL,  -- 'json data'
  fld_date DATE DEFAULT NULL,  -- Stores only the date (YYYY-MM-DD)
  fld_time TIME DEFAULT NULL,  -- Stores only the time (HH:MI:SS)
  fld_timestamp TIMESTAMP DEFAULT NULL, -- Stores date and time without timezone
  fld_timestamptz TIMESTAMP WITH TIME ZONE DEFAULT NULL, -- Stores date and time with timezone
  fld_timestamp_micro TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT NULL, -- Stores date and time with timezone
  fld_timestamptz_micro TIMESTAMP(6) WITH TIME ZONE DEFAULT NULL, -- Stores date and time with timezone
  fld_bool BOOLEAN DEFAULT TRUE,
  orm_other_id INT DEFAULT NULL,
  orm_third_key VARCHAR(45) DEFAULT NULL,

  CONSTRAINT fk_orm_other_id FOREIGN KEY (orm_other_id) REFERENCES orm_other (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_orm_third_key FOREIGN KEY (orm_third_key) REFERENCES orm_third (fk_to_this)
);

CREATE INDEX idx_orm_other_id ON orm_test (orm_other_id);
CREATE INDEX fk_orm_third_idx ON orm_test (orm_third_key);

INSERT INTO orm_test VALUES
 (1,1,2,3,4,1.10,1.10,1.10,'aSYNC','a varchar 0','a text 0','a mediumtext 0',NULL,ARRAY['foo', 'bar1'],'2025-05-01', '01:30:00','2025-05-01 14:30:00','2025-05-01 14:30:00+02','2025-05-01 14:30:00.123456','2025-05-01 14:30:00.123456+02',true, NULL,NULL),
 (2,2,3,4,5,2.10,2.10,2.10,'bSYNC','a varchar 1','a text 1','a mediumtext 1',NULL,ARRAY['foo', 'bar2', 'baz'],'2025-05-02', '02:30:00','2025-05-02 14:30:00','2025-05-02 14:30:00+02','2025-05-02 14:30:00.123456','2025-05-02 14:30:00.123456+02',true, NULL,'third hali'),
 (3,3,4,5,6,3.10,3.10,3.10,'cSYNC','a varchar 2','a text 2','a mediumtext 2',NULL,ARRAY['foo', 'bar3'],'2025-05-03', '03:30:00','2025-05-03 14:30:00','2025-05-03 14:30:00+02','2025-05-03 14:30:00.123456','2025-05-03 14:30:00.123456+02',true, NULL,NULL),
 (4,4,5,6,7,4.10,4.10,4.10,'dSYNC','a varchar 3','a text 3','a mediumtext 3',NULL,ARRAY['foo', 'bar4', 'baz'],'2025-05-04', '04:30:00','2025-05-04 14:30:00','2025-05-04 14:30:00+02','2025-05-04 14:30:00.123456','2025-05-04 14:30:00.123456+02',true, NULL,NULL),
 (5,5,6,7,8,5.10,5.10,5.10,'eSYNC','a varchar 4','a text 4','a mediumtext 4',NULL,ARRAY['foo', 'bar5'],'2025-05-05', '05:30:00','2025-05-05 14:30:00','2025-05-05 14:30:00+02','2025-05-05 14:30:00.123456','2025-05-05 14:30:00.123456+02',true, NULL,NULL),
 (6,6,7,8,9,6.10,6.10,6.10,'fSYNC','a varchar 5','a text 5','a mediumtext 5',NULL,ARRAY['foo', 'bar6', 'baz'],'2025-05-06', '06:30:00','2025-05-06 14:30:00','2025-05-06 14:30:00+02','2025-05-06 14:30:00.123456','2025-05-06 14:30:00.123456+02',true, NULL,NULL),
 (7,7,8,9,10,7.10,7.10,7.10,'gSYNC','a varchar 6','a text 6','a mediumtext 6',NULL,ARRAY['foo', 'bar7'],'2025-05-07', '07:30:00','2025-05-07 14:30:00','2025-05-07 14:30:00+02','2025-05-07 14:30:00.123456','2025-05-07 14:30:00.123456+02',true, NULL,NULL),
 (8,8,9,10,11,8.10,8.10,8.10,'hSYNC','a varchar 7','a text 7','a mediumtext 7',NULL,ARRAY['foo', 'bar8', 'baz'],'2025-05-08', '08:30:00','2025-05-08 14:30:00','2025-05-08 14:30:00+02','2025-05-08 14:30:00.123456','2025-05-08 14:30:00.123456+02',true, NULL,NULL),
 (9,9,10,11,12,9.10,9.10,9.10,'iSYNC','a varchar 8','a text 8','a mediumtext 8',NULL,ARRAY['foo', 'bar9'],'2025-05-09', '09:30:00','2025-05-09 14:30:00','2025-05-09 14:30:00+02','2025-05-09 14:30:00.123456','2025-05-09 14:30:00.123456+02',true, NULL,NULL),
 (10,10,11,12,13,10.10,10.10,10.10,'jSYNC','a varchar 9','a text 9','a mediumtext 9',NULL,ARRAY['foo', 'bar10', 'baz'],'2025-05-10', '10:30:00','2025-05-10 14:30:00','2025-05-10 14:30:00+02','2025-05-10 14:30:00.123456','2025-05-10 14:30:00.123456+02',true, 1,NULL);

SELECT setval('public.orm_test_id_seq', (SELECT max(id) FROM public.orm_test));

drop table if exists db_keywords;
CREATE TABLE db_keywords (
 id SERIAL PRIMARY KEY,
 "null" varchar(128) COLLATE "C" NOT NULL,
 "class" varchar(32) COLLATE "C" NOT NULL,
 "int" int NULL
);