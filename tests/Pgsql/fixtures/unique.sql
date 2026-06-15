DROP TABLE IF EXISTS orm_other CASCADE;
CREATE TABLE orm_other (
    id SERIAL PRIMARY KEY,
    fld_int int NOT NULL,
    title varchar(45) DEFAULT NULL
);
CREATE UNIQUE INDEX idx_orm_other_uniq ON orm_other (fld_int);