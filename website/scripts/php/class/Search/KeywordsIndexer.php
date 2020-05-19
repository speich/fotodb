<?php

namespace PhotoDatabase\Search;

use PDO;


/**
 * Class KeywordsIndexer
 * Creates a fulltext search index for keywords using fts4 based on most tables and columns in the database.
 * The fts uses the unicode64 tokenizer compiled with sqlite3.
 */
class KeywordsIndexer extends Indexer
{
    /**
     * @var PDO
     */
    public $db;

    /**
     * Fts4Indexer constructor.
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @return int
     */
    public function init(): int
    {
        /* note: unlike ordinary fts4 tables, contentless tables require an explicit integer docid value to be provided. External content tables are assumed to have
            a unique Id too. Therefore we cannot use a view as the external content, since that does not have a unique id. */
        $sql = 'BEGIN;
            DROP TABLE IF EXISTS SearchKeywords_fts; 
            CREATE VIRTUAL TABLE SearchKeywords_fts USING fts4(Keyword, tokenize=unicode61);
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
              INSERT INTO SearchKeywords_fts(Keyword) 
              SELECT Keyword FROM (".$this->sqlSource.") WHERE Keyword != '';
              COMMIT;";

        return $this->db->exec($sql);
    }
}
