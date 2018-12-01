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



;
DROP TABLE SearchImages_fts;
/* note: unlike ordinary fts4 tables, contentless tables required an explicit integer docid value to be provided. External content tables are assumed to have
 a unique Id too. Therefore we cannot use a view as the external content, since that does not have a unique id. */
CREATE VIRTUAL TABLE SearchImages_fts USING fts4(imgId, keyword, tokenize=unicode61);
INSERT INTO SearchImages_fts(keyword) SELECT keyword FROM (
	SELECT ImgName keyword
	FROM Images
	UNION
	SELECT ImgTitle
	FROM Images
	UNION
	SELECT ImgDesc
	FROM Images
	UNION
	SELECT c.NameDe
	FROM Images i
	     INNER JOIN Countries c ON i.CountryId = c.Id
	UNION
	SELECT k.Name
	FROM Images i
	     INNER JOIN Images_Keywords ik ON i.Id = ik.ImgId
	     INNER JOIN Keywords k ON ik.KeywordId = k.Id
	UNION
	SELECT l.Name
	FROM Images i
	     INNER JOIN Images_Locations il ON il.ImgId = i.Id
	     INNER JOIN Locations l ON il.LocationId = l.Id
	UNION
	SELECT s.NameDe
	FROM Images i
	     INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
	     INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
	UNION
	SELECT s.NameLa
	FROM Images i
	     INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
	     INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
	UNION
	SELECT t.NameDe
	FROM Images i
	     INNER JOIN Images_Themes it ON i.Id = it.ImgId
	     INNER JOIN Themes t ON it.ThemeId = t.Id
	UNION
	SELECT a.NameDe
	FROM Images i
	     INNER JOIN Images_Themes it ON i.Id = it.ImgId
	     INNER JOIN Themes t ON it.ThemeId = t.Id
	     INNER JOIN SubjectAreas a ON t.SubjectAreaId = a.Id
)
WHERE keyword != '';
;

	SELECT snippet(SearchImages_fts, '<b>', '</b>', '<b>...</b>',-1, 5) keyword
	FROM SearchImages_fts si
	WHERE (SearchImages_fts MATCH 'tonia NEAR/3 seraina')

;


SELECT keyword FROM SearchImages_fts si
WHERE  (SearchImages_fts MATCH 'ver*')



;


--CREATE VIEW SearchContent_v AS

	SELECT i.Id rowid, i.ImgName, i.ImgTitle, i.ImgDesc,
		c.NameDe country,
		k.keywords,
		l.locations,
		s.NameDe commonNames, s.NameLa scientificNames,
		t.NameDe themes,
		a.NameDe subjectAreas
	FROM Images i
	     LEFT JOIN Countries c ON i.CountryId = c.Id
	     LEFT JOIN (
			SELECT ik.imgId, GROUP_CONCAT(k.Name, ', ') keywords FROM Images_Keywords ik
			INNER JOIN Keywords k ON ik.KeywordId = k.Id
			WHERE k.Name NOT NULL
			GROUP BY ik.ImgId
		) k ON i.Id = k.ImgId
	     LEFT JOIN (
	        SELECT il.ImgId, GROUP_CONCAT(l.Name, ', ') locations FROM Images_Locations il
	        LEFT JOIN Locations l ON il.LocationId = l.Id
	         WHERE l.Name NOT NULL
	         GROUP BY il.ImgId
	      ) l ON i.Id = l.ImgId
	     LEFT JOIN (
	         SELECT sc.ImgId, GROUP_CONCAT(s.NameDe, ', ') commonNames FROM Images_ScientificNames sc
			LEFT JOIN ScientificNames s ON sc.ScientificNameId = s.Id
		         WHERE s.NameDe NOT NULL
	         GROUP BY sc.ImgId
			) s ON i.Id = s.ImgId
	     LEFT JOIN Images_Themes it ON i.Id = it.ImgId
	     LEFT JOIN Themes t ON it.ThemeId = t.Id
	     LEFT JOIN SubjectAreas a ON t.SubjectAreaId = a.Id
	GROUP BY i.Id, i.ImgName, i.ImgTitle, i.ImgDesc, c.NameDe,
	         k.Name, l.Name, s.NameDe, s.NameLa, t.NameDe, a.NameDe
) t
GROUP BY t.rowid, t.ImgName, t.ImgTitle, t.ImgDesc


;

SELECT i.Id rowid, i.ImgName, i.ImgTitle, i.ImgDesc,
	k.keywords
FROM Images i
LEFT JOIN (
		SELECT ik.ImgId, group_concat(k.Name, ', ') keywords
		FROM Keywords k
		     INNER JOIN Images_Keywords ik ON k.Id = ik.KeywordId
		GROUP BY ik.ImgId
	) k ON i.Id = k.ImgId