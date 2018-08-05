-- create initial test data
CREATE TABLE Test (id INTEGER PRIMARY KEY, cont);
--CREATE TABLE Test_noint (id PRIMARY KEY, cont);
INSERT INTO Test (id, cont) VALUES (75, 'bla');
INSERT INTO Test (id, cont) VALUES (76, 'crr');
INSERT INTO Test (id, cont) VALUES (1, 'Vogel');
INSERT INTO Test (id, cont) VALUES (2, 'Vögel');
INSERT INTO Test (id, cont) VALUES (3, 'Sommervogels');
INSERT INTO Test (id, cont) VALUES (4, 'Ein Sperber schlägt mit den Flügeln');


--INSERT INTO Test_noint (id, cont) VALUES (75, 'bla');
CREATE VIEW Test_v AS SELECT id rowid, cont FROM Test;

SELECT * FROM Test_v;
SELECT * FROM Test WHERE id = 75;        -- 75 bla
--SELECT * FROM Test_noint WHERE id = 75;  -- 75 bla
--SELECT * FROM Test_v WHERE id = 75;      -- 75 bla

-- create fulltext search index using fts4 extension
--CREATE VIRTUAL TABLE Search_nc USING fts4(cont);
--CREATE VIRTUAL TABLE Search USING fts4(content="Test", cont);
--CREATE VIRTUAL TABLE Search_noint USING fts4(content="Test_noint", cont);
CREATE VIRTUAL TABLE Search_v USING fts4(cont, content="Test_v", tokenize=unicode61);
--CREATE VIRTUAL TABLE Search_vnc USING fts4(cont);
--INSERT INTO Search_nc(rowid, cont) SELECT id, cont FROM Test;
--INSERT INTO Search(rowid, cont) SELECT id, cont FROM Test;
--INSERT INTO Search_noint(rowid, cont) SELECT id, cont FROM Test_noint;
INSERT INTO Search_v(rowid, cont) SELECT rowid, cont FROM Test_v;
/*

SELECT rowid, * FROM Search_nc;                             -- 75 bla
SELECT rowid, * FROM Search_nc WHERE rowid = 75;            -- 75 bla
SELECT rowid, * FROM Search_nc WHERE Search_nc MATCH 'b*';  -- 75 bla
SELECT rowid, * FROM Search;                                -- 75 bla
SELECT rowid, * FROM Search WHERE rowid = 75;               -- 75 bla
SELECT rowid, * FROM Search WHERE Search MATCH 'c*';        -- 75 bla
SELECT rowid, * FROM Search_noint;                          --  1 bla
SELECT rowid, * FROM Search_noint WHERE rowid = 1;          --  1 bla
SELECT rowid, * FROM Search_noint WHERE rowid = 75;         -- no rows returned
SELECT rowid, * FROM Search_noint WHERE Search_noint MATCH 'b*';    -- 75 <null>
*/
SELECT rowid, * FROM Search_v;                                      -- 0  bla
SELECT rowid, * FROM Search_v WHERE rowid = 0;              -- no rows returned
SELECT rowid, * FROM Search_v WHERE rowid = 75;             -- no rows returned
SELECT rowid, * FROM Search_v WHERE Search_v MATCH 'cr*';    --75  <null>
SELECT rowid, * FROM Search_v WHERE Search_v MATCH 'mit*';    --75  <null>



UPDATE Test SET cont = 'aha' WHERE id = 75; -- with external content table, you will see 'aha' but index is not updated
--INSERT INTO Search(Search) VALUES('rebuild');
INSERT INTO Search_v(Search_v) VALUES('rebuild');
DELETE FROM Test WHERE id = 76;

/*
 *triggers to maintain fts.sqlite index
*/
-- INSERT
CREATE TRIGGER Test_ai AFTER INSERT ON Test BEGIN
  INSERT INTO Search_v(rowid, cont) SELECT rowid, cont FROM Test_v WHERE rowid = new.rowid;
END;
/*
In order to keep an FTS in sync with an external content table,
any UPDATE or DELETE operations must be applied first to the FTS table, and then to the external content table.
*/
-- UPDATE
CREATE TRIGGER Test_bu BEFORE UPDATE ON Test BEGIN
  DELETE FROM Search_v WHERE rowid=old.rowid;
END;
CREATE TRIGGER Test_au AFTER UPDATE ON Test BEGIN
  INSERT INTO Search_v(rowid, cont) SELECT rowid, cont FROM Test_v WHERE rowid = new.rowid;
END;
-- DELETE
CREATE TRIGGER Test_bd BEFORE DELETE ON Test BEGIN
  DELETE FROM Search_v WHERE rowid=old.rowid;
END;


DROP TABLE Test;
--DROP TABLE Test_noint;
DROP VIEW Test_v;
--DROP TABLE Search_nc;
--DROP TABLE Search;
--DROP TABLE Search_noint;
DROP TABLE Search_v;
SELECT * FROM sqlite_master WHERE type = 'trigger';
--DROP TRIGGER Images_ai;














