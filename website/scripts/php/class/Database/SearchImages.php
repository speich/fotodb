<?php

namespace PhotoDatabase\Database;

use PDO;


class SearchImages
{

    /** @var Database */
    private $db;

    /**
     * Search constructor.
     * @param PDO $db database to create image search index in
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Create the database tables necessary for searching.
     */
    public function createStructure()
    {

        $sql = "BEGIN;
            CREATE VIEW SearchImages_v AS
            -- note: query should return records in a way that rowId is unique for fts4
            SELECT i.Id rowid, /* when using a view for the content table, only rowid is accepted */
                i.ImgName, i.ImgTitle, i.ImgDesc,
                c.NameDe Country,
                k.Keywords,
                l.Locations,
                s.CommonNames,
                sc.ScientificNames,
                t.Themes,
                a.SubjectAreas
            FROM Images i
            LEFT JOIN Countries c ON i.CountryId = c.Id
            LEFT JOIN (
                SELECT ik.imgId, GROUP_CONCAT(k.Name, ', ') Keywords FROM Images_Keywords ik
                INNER JOIN Keywords k ON ik.KeywordId = k.Id
                WHERE k.Name NOT NULL
                GROUP BY ik.ImgId
            ) k ON i.Id = k.ImgId
            LEFT JOIN (
                SELECT il.ImgId, GROUP_CONCAT(l.Name, ', ') Locations FROM Images_Locations il
                INNER JOIN Locations l ON il.LocationId = l.Id
                WHERE l.Name NOT NULL
                GROUP BY il.ImgId
            ) l ON i.Id = l.ImgId
            LEFT JOIN (
                SELECT sc.ImgId, GROUP_CONCAT(s.NameDe, ', ') CommonNames FROM Images_ScientificNames sc
                INNER JOIN ScientificNames s ON sc.ScientificNameId = s.Id
                WHERE s.NameDe NOT NULL
                GROUP BY sc.ImgId
            ) s ON i.Id = s.ImgId
            LEFT JOIN (
                SELECT sc.ImgId, GROUP_CONCAT(s.NameLa, ', ') ScientificNames FROM Images_ScientificNames sc
                INNER JOIN ScientificNames s ON sc.ScientificNameId = s.Id
                WHERE s.NameLa NOT NULL
                GROUP BY sc.ImgId
            ) sc ON i.Id = sc.ImgId
            LEFT JOIN (
                SELECT it.ImgId, GROUP_CONCAT(t.NameDe, ', ') Themes FROM Images_Themes it
                INNER JOIN Themes t ON it.ThemeId = t.Id
                GROUP BY it.ImgId
            ) t ON i.Id = t.ImgId
            LEFT JOIN (
                SELECT it.ImgId, GROUP_CONCAT(s.NameDe, ', ') SubjectAreas FROM Images_Themes it
                INNER JOIN Themes t ON it.ThemeId = t.Id
                INNER JOIN SubjectAreas s ON t.SubjectAreaId = s.Id
                GROUP BY it.ImgId
            ) a ON i.Id = a.ImgId;
            /* CREATE VIRTUAL TABLE SearchImages_fts USING fts4(content=SearchImages_v, ImgName, ImgTitle, ImgDesc, Country, Keywords, Locations, CommonNames, ScientificNames, Themes, SubjectAreas, tokenize=unicode61);   -- important: do not pass the row id column !*/
			COMMIT;";

        return $this->db->exec($sql);

    }

    public function populate()
    {
        // Check if structure for searching was already created otherwise create it
        $sql = "INSERT INTO SearchImages_fts(rowid, ImgName, ImgTitle, ImgDesc, Country, Keywords, Locations, CommonNames, ScientificNames, Themes, SubjectAreas)
            SELECT rowid, ImgName, ImgTitle, ImgDesc, Country, Keywords, Locations, CommonNames, ScientificNames, Themes, SubjectAreas FROM SearchImages_v";

        return $this->db->exec($sql);
    }

}
