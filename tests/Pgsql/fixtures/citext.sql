-- case insensitive text
CREATE EXTENSION IF NOT EXISTS citext;

DROP TABLE IF EXISTS citext_test CASCADE;
CREATE TABLE citext_test (
    id SERIAL primary KEY,
    citxt_col citext NULL
);

INSERT INTO citext_test (citxt_col) VALUES ('Case Insensitive Text');