<?php

namespace PhotoDatabase\Database;

use PDO;


class SearchKeywords
{
    /** @var Database */
    private $db;

    /**
     * Search constructor.
     * @param PDO $db database to create search index of keywords
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Create the database tables necessary for searching.
     * Note: previous export will be dropped
     * @return int
     */
    public function createStructure()
    {
        $sql = "BEGIN;
            /* note: unlike ordinary fts4 tables, contentless tables required an explicit integer docid value to be provided. External content tables are assumed to have
             a unique Id too. Therefore we cannot use a view as the external content, since that does not have a unique id. */
            CREATE VIRTUAL TABLE SearchKeywords_fts USING fts4(Keyword, tokenize=unicode61);            
			COMMIT;";

        return $this->db->exec($sql);
    }

    /**
     * @return int
     */
    public function populate()
    {
        $sql = "BEGIN;
            INSERT INTO SearchKeywords_fts(Keyword) SELECT Keyword FROM (
                SELECT ImgName Keyword FROM Images WHERE Public = 1
                UNION
                SELECT ImgTitle FROM Images WHERE Public = 1
                UNION
                SELECT ImgDesc FROM Images WHERE Public = 1
                UNION
                SELECT c.NameDe FROM Images i
                INNER JOIN Countries c ON i.CountryId = c.Id
                WHERE i.Public = 1
                UNION
                SELECT k.Name FROM Images i
                INNER JOIN Images_Keywords ik ON i.Id = ik.ImgId
                INNER JOIN Keywords k ON ik.KeywordId = k.Id
                WHERE i.Public = 1
                UNION
                SELECT l.Name FROM Images i
                INNER JOIN Images_Locations il ON il.ImgId = i.Id
                INNER JOIN Locations l ON il.LocationId = l.Id
                WHERE i.Public = 1
                UNION
                SELECT s.NameDe FROM Images i
                INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
                INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
                WHERE i.Public = 1
                UNION
                SELECT s.NameLa FROM Images i
                INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
                INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
                WHERE i.Public = 1
                UNION
                SELECT t.NameDe FROM Images i
                INNER JOIN Images_Themes it ON i.Id = it.ImgId
                INNER JOIN Themes t ON it.ThemeId = t.Id
                WHERE i.Public = 1
                UNION
                SELECT a.NameDe FROM Images i
                INNER JOIN Images_Themes it ON i.Id = it.ImgId
                INNER JOIN Themes t ON it.ThemeId = t.Id
                INNER JOIN SubjectAreas a ON t.SubjectAreaId = a.Id
                WHERE i.Public = 1
            )
            WHERE Keyword != '';
            COMMIT;";

        return $this->db->exec($sql);
    }

}
