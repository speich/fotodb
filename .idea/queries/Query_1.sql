CREATE TABLE Test (docid INTEGER PRIMARY KEY, content);
CREATE TABLE Test_noint (docid PRIMARY KEY, content);
INSERT INTO Test (docid, content) VALUES (75, 'bla');
INSERT INTO Test_noint (docid, content) VALUES (75, 'bla');
CREATE VIEW Test_v AS SELECT docid, content FROM Test;

SELECT * FROM Test WHERE docid = 75;        -- 75 bla
SELECT * FROM Test_noint WHERE docid = 75;  -- 75 bla
SELECT * FROM Test_v WHERE docid = 75;      -- 75 bla

-- create fulltext search index using fts4 extension
CREATE VIRTUAL TABLE Search_nc USING fts4(content);
CREATE VIRTUAL TABLE Search USING fts4(content="Test", content);
CREATE VIRTUAL TABLE Search_noint USING fts4(content="Test_noint", content);
CREATE VIRTUAL TABLE Search_v USING fts4(content="Test_v", content);
INSERT INTO Search_nc(docid, content) SELECT docid, content FROM Test;
INSERT INTO Search(docid, content) SELECT docid, content FROM Test;
INSERT INTO Search_noint(docid, content) SELECT docid, content FROM Test_noint;
INSERT INTO Search_v(docid, content) SELECT docid, content FROM Test_v;

SELECT docid, * FROM Search_nc WHERE docid = 75;            -- 75 bla
SELECT docid, * FROM Search_nc WHERE Search_nc MATCH 'b*';  -- 75 bla
SELECT docid, * FROM Search;                                -- 75 bla
SELECT docid, * FROM Search WHERE docid = 75;               -- 75 bla
SELECT docid, * FROM Search WHERE Search MATCH 'b*';        -- 75 bla
SELECT docid, * FROM Search_noint;                          --  1 bla
SELECT docid, * FROM Search_noint WHERE docid = 1;          --  1 bla
SELECT docid, * FROM Search_noint WHERE docid = 75;         -- no rows returned
SELECT docid, * FROM Search_noint WHERE Search_noint MATCH 'b*';    -- 75 <null>
SELECT docid, * FROM Search_v;                                      -- 0  bla
SELECT docid, * FROM Search_v WHERE docid = 0;              -- no rows returned
SELECT docid, * FROM Search_v WHERE docid = 75;             -- no rows returned
SELECT docid, * FROM Search_v WHERE Search_v MATCH 'b*';    --75  <null>


DROP TABLE Test;
DROP VIEW Test_v;
DROP TABLE Search_nc;
DROP TABLE Search;
DROP TABLE Search_noint;
DROP TABLE Search_v;


