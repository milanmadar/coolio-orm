DROP TABLE IF EXISTS orm_json_test CASCADE;
CREATE TABLE orm_json_test (
  id SERIAL PRIMARY KEY,
  fld_jsonb JSONB DEFAULT NULL,
  fld_json JSON DEFAULT NULL
);

INSERT INTO orm_json_test (fld_jsonb, fld_json) VALUES
(
  '{"str": "lollypop", "str_quotes": "He''s about to say \"hi\"", "num_int": 10, "num_float": 10.5, "bool": true, "null_value": null, "array": [1, 2, 3], "object": {"key": "value"}}',
  '{"str": "lollypop", "str_quotes": "He''s about to say \"hi\"", "num_int": 10, "num_float": 10.5, "bool": true, "null_value": null, "array": [1, 2, 3], "object": {"key": "value"}}'
),(
  '["apple", "banana", "cherry"]',
  '["apple", "banana", "cherry"]'
),(
  '{"employees":[{"firstName":"John","lastName":"Doe"},{"firstName":"Anna","lastName":"Smith"},{"firstName":"Peter","lastName":"Jones"}]}',
  '{"employees":[{"firstName":"John","lastName":"Doe"},{"firstName":"Anna","lastName":"Smith"},{"firstName":"Peter","lastName":"Jones"}]}'
),(
  '{"a":1,"b":2,"c":3}',
  '{"a":1,"b":2,"c":3}'
)