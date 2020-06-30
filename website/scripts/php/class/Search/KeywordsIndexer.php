<?php

namespace PhotoDatabase\Search;

/**
 * Class KeywordsIndexer
 * Creates a fulltext search index for keywords using fts4 based on most tables and columns in the database.
 * The fts uses the unicode64 tokenizer compiled with sqlite3.
 */
class KeywordsIndexer extends Indexer
{
    /**
     * @return int
     */
    public function init(): int
    {
        /* note: unlike ordinary fts4 tables, contentless tables require an explicit integer docid value to be provided. External content tables are assumed to have
            a unique Id too. Therefore we cannot use a view as the external content, since that does not have a unique id. */
        $options = $this->isTokenizerUnicode61() ? 'Keyword, tokenize=unicode61' : 'KeywordOrig, KeywordMod';
        $sql = 'BEGIN;
            DROP TABLE IF EXISTS Keywords_fts; 
            CREATE VIRTUAL TABLE Keywords_fts USING fts4('.$options.');
            COMMIT;';

        return $this->db->exec($sql);
    }

    /**
     * Fills the virtual table with keywords.
     * Note: Automatically removes diacritics. The unmodified words are stored in the column KeywordOrig, while the ones with
     * diacritics removed, are stored in KeywordMod.
     * TODO: improve search by creating variants of each word by removing syllables from the beginning of the word to simulate prefix search, e.g.
     *      "Waldverjüngung" -> Waldverjungung -> Wald-ver-jüng-ung
     *      e.g. stores      KeywordOrig | KeywordMod
     *                    Waldverjüngung | Waldverjungung
     *                    Waldverjüngung | verjungung
     *                    Waldverjüngung | jungung
     *                    Waldverjüngung | ung
     *  use https://github.com/vanderlee/phpSyllable to hyphenate
     * @return int number of affected records
     */
    public function populate(): int
    {
        $sqlTok = "INSERT INTO Keywords_fts(Keyword) ".$this->sqlSource->get();
            //SELECT Keyword FROM (".$this->sqlSource->getFrom().") WHERE Keyword != '';";
        $sqlNoTok = "INSERT INTO Keywords_fts(KeywordOrig, KeywordMod) 
            SELECT REMOVE_DIACRITICS(Keyword), Keyword FROM (".$this->sqlSource->get().");";
        $sql = "BEGIN;".
            ($this->isTokenizerUnicode61() === true ? $sqlTok : $sqlNoTok).
            "COMMIT;";

        return $this->db->exec($sql);
    }
}
