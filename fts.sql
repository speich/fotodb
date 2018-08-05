/***
   create initial example data
***/

-- list with image data
CREATE TABLE Images (id INTEGER PRIMARY KEY, title);
INSERT INTO Images (id, title) VALUES (1, 'Flying bird');
INSERT INTO Images (id, title) VALUES (2, 'Owl at night');
INSERT INTO Images (id, title) VALUES (3, 'Portrait of a blackbird');
INSERT INTO Images (id, title) VALUES (4, 'Beating wings');
INSERT INTO Images (id, title) VALUES (6, 'taking a bath');
INSERT INTO Images (id, title) VALUES (7, 'Running on the ground');
-- list of names of birds
CREATE TABLE BirdNames (id INTEGER PRIMARY KEY, scientific, eng, deu);
INSERT INTO BirdNames (id, scientific, eng, deu) VALUES (1, 'Accipiter fasciatus', 'Bänderhabicht',	'Brown Goshawk');
INSERT INTO BirdNames (id, scientific, eng, deu) VALUES (2, 'Acrocephalus arundinaceus', 'Great Reed-Warbler', 'Drosselrohrsänger');
INSERT INTO BirdNames (id, scientific, eng, deu) VALUES (3, 'Calandrella brachydactyla', 'Greater Short-toed Lark', 'Kurzzehenlerche');
-- an image can show zero, one or more birds
CREATE TABLE Images_BirdNames (imgId /* FOREIGN KEY REFERENCES */ Images, nameId /* FOREIGN KEY REFERENCES */ BirdNames);
INSERT INTO Images_BirdNames (imgId, nameId) VALUES (1, 1);
INSERT INTO Images_BirdNames (imgId, nameId) VALUES (4, 1);
INSERT INTO Images_BirdNames (imgId, nameId) VALUES (6, 2);
INSERT INTO Images_BirdNames (imgId, nameId) VALUES (6, 3);

-- show screenshot of view SearchContent_v here

-- create and populate fts4 tables
CREATE VIRTUAL TABLE SearchImages_v USING fts4(content="Images", title, tokenize=unicode61);
CREATE VIRTUAL TABLE SearchBirdNames_v USING fts4(content="BirdNames", scientific, eng, deu, tokenize=unicode61);
INSERT INTO SearchImages_v(rowid, title) SELECT id, title FROM Images;
INSERT INTO SearchBirdNames_v(rowid, scientific, eng, deu) SELECT id, scientific, eng, deu FROM BirdNames;
-- query created fts
SELECT * FROM SearchImages_v si
LEFT JOIN Images_BirdNames ib ON si.rowid = ib.imgId
LEFT JOIN SearchBirdNames_v sb ON ib.nameId = sb.rowid
WHERE (SearchImages_v MATCH 'wing*') OR (SearchBirdNames_v MATCH 'wing*');
-- --> above query creates error:
-- unable to use function MATCH in requested context

-- you have to use a UNION for the query
SELECT si.rowId imgId, si.title, null nameId, null scientific, null eng, null deu  FROM SearchImages_v si
WHERE  (SearchImages_v MATCH :word||'*')
UNION
SELECT ib.imgId, null, sb.rowid nameId, sb.scientific, sb.eng, sb.deu FROM SearchBirdNames_v sb
INNER JOIN Images_BirdNames ib ON sb.rowid = ib.nameId
WHERE  (SearchBirdNames_v MATCH :word||'*');


-- Different solution: aggregate all data into a view and use that as the content table for the FTS
-- => only one content table, but multiple records for image
CREATE VIEW SearchContent_v AS
  SELECT i.id rowid, i.title,
         b.scientific, b.eng, b.deu
  FROM Images i
         LEFT JOIN Images_BirdNames ib ON i.id = ib.imgId
         LEFT JOIN BirdNames b ON ib.nameId = b.id
;

-- create triggers to keep index in sync with content tables
-- INSERT
CREATE TRIGGER Images_ai AFTER INSERT ON Images BEGIN
  INSERT INTO SearchImages_v(rowid, title) VALUES (new.id, new.title);
END;
CREATE TRIGGER BirdNames_ai AFTER INSERT ON BirdNames BEGIN
  INSERT INTO SearchBirdNames_v(rowid, scientific, eng, deu) VALUES (new.id, new.scientific, new.eng, new.deu);
END;
/*
In order to keep an FTS in sync with an external content table,
any UPDATE or DELETE operations must be applied first to the FTS table, and then to the external content table.
The DELETE trigger must be fired before the actual delete takes place on the content table. This is so that FTS4 can still r
etrieve the original values in order to update the full-text index. And the INSERT trigger must be fired after the new
row is inserted, so as to handle the case where the rowid is assigned automatically within the system.
The UPDATE trigger must be split into two parts, one fired before and one after the update of the content table,
for the same reasons.
*/
-- UPDATE
CREATE TRIGGER Images_bu BEFORE UPDATE ON Images BEGIN
  DELETE FROM SearchImages_v WHERE rowid = old.id;
END;
CREATE TRIGGER Images_au AFTER UPDATE ON Images BEGIN
  INSERT INTO SearchImages_v(rowid, title) VALUES (new.id, new.title);
END;
CREATE TRIGGER BirdNames_bu BEFORE UPDATE ON BirdNames BEGIN
  DELETE FROM SearchBirdNames_v WHERE rowid = old.id;
END;
CREATE TRIGGER BirdNames_au AFTER UPDATE ON BirdNames BEGIN
  INSERT INTO SearchBirdNames_v(rowid, scientific, eng, deu) VALUES (new.id, new.scientific, new.eng, new.deu);
END;
-- DELETE
CREATE TRIGGER Images_bd BEFORE DELETE ON Images BEGIN
  DELETE FROM SearchImages_v WHERE rowid = old.id;
END;
CREATE TRIGGER BirdNames_bd BEFORE DELETE ON BirdNames BEGIN
  DELETE FROM SearchBirdNames_v WHERE rowid = old.id;
END;

-- test triggers by inserting, updating and deleting
INSERT INTO Images (title) VALUES ('new bird twitched');
SELECT rowid, * FROM SearchImages_v WHERE SearchImages_v MATCH 'twitched';
UPDATE Images SET title = 'another bird twitched' WHERE id = 8;
SELECT rowid, * FROM SearchImages_v WHERE SearchImages_v MATCH 'twitched';
DELETE FROM Images WHERE id = 8;
SELECT rowid, * FROM SearchImages_v WHERE SearchImages_v MATCH 'twitched';

INSERT INTO BirdNames (scientific, eng, deu) VALUES ('Turdus merula', 'blackbird', 'Amsel');
SELECT rowid, * FROM SearchBirdNames_v WHERE SearchBirdNames_v MATCH 'twitched';
UPDATE BirdNames SET title = 'another bird twitched' WHERE id = 8;
SELECT rowid, * FROM SearchBirdNames_v WHERE SearchBirdNames_v MATCH 'twitched';
DELETE FROM BirdNames WHERE id = 8;
SELECT rowid, * FROM SearchBirdNames_v WHERE SearchBirdNames_v MATCH 'twitched';