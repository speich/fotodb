<?php

namespace PhotoDatabase\Search;

use PDO;
use Transliterator;


/**
 * Class SearchKeywords
 * Provides a list of (key)words found in all relevant columns of all relevant tables to display
 * @package PhotoDatabase\Database
 */
class KeywordsIndexerNoUnicode implements Fts4Indexer
{
    /**
     * @var PDO
     */
    public $db;

    /**
     * Fts4Indexer constructor.
     * @param PDO $db
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->db->sqliteCreateFunction('REMOVE_DIACRITICS', ['PhotoDatabase\Search\FtsFunctions', 'removeDiacritics']);

    }

    /**
     * @return int
     */
    public function init(): int
    {
        /* note: unlike ordinary fts4 tables, contentless tables require an explicit integer docid value to be provided. External content tables are assumed to have
            a unique Id too. Therefore we cannot use a view as the external content, since that does not have a unique id. */
        $sql = 'BEGIN;
            DROP TABLE IF EXISTS SearchKeywords2_fts; 
            CREATE VIRTUAL TABLE SearchKeywords2_fts USING fts4(KeywordMod, KeywordOrig);
            COMMIT;';

        return $this->db->exec($sql);
    }

    /**
       * Fills the virtual table with keywords.
       * Note: automatically removes diacritics. The unmodified words are stored in the column KeywordOrig, while the ones with
       * diacritics removed, are stored in KeywordMod.
       * @return int number of affected records
       */
      public function populate(): int
      {
          $sql = "BEGIN;
            INSERT INTO SearchKeywords2_fts(KeywordMod, KeywordOrig) 
            SELECT REMOVE_DIACRITICS(Keyword), Keyword FROM (
                  SELECT ImgName Keyword FROM ImagesIndexer WHERE Public = 1
                  UNION
                  SELECT ImgTitle FROM ImagesIndexer WHERE Public = 1
                  UNION
                  SELECT ImgDesc FROM ImagesIndexer WHERE Public = 1
                  UNION
                  SELECT c.NameDe FROM ImagesIndexer i
                  INNER JOIN Countries c ON i.CountryId = c.Id
                  WHERE i.Public = 1
                  UNION
                  SELECT k.Name FROM ImagesIndexer i
                  INNER JOIN Images_Keywords ik ON i.Id = ik.ImgId
                  INNER JOIN Keywords k ON ik.KeywordId = k.Id
                  WHERE i.Public = 1
                  UNION
                  SELECT l.Name FROM ImagesIndexer i
                  INNER JOIN Images_Locations il ON il.ImgId = i.Id
                  INNER JOIN Locations l ON il.LocationId = l.Id
                  WHERE i.Public = 1
                  UNION
                  SELECT s.NameDe FROM ImagesIndexer i
                  INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
                  INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
                  WHERE i.Public = 1
                  UNION
                  SELECT s.NameLa FROM ImagesIndexer i
                  INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
                  INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
                  WHERE i.Public = 1
                  UNION
                  SELECT t.NameDe FROM ImagesIndexer i
                  INNER JOIN Images_Themes it ON i.Id = it.ImgId
                  INNER JOIN Themes t ON it.ThemeId = t.Id
                  WHERE i.Public = 1
                  UNION
                  SELECT a.NameDe FROM ImagesIndexer i
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
