<?php

namespace PhotoDatabase\Search;

use PDO;
use PDOException;


/**
 * Class Indexer
 * @package PhotoDatabase\Search
 */
abstract class Indexer implements Fts4Indexer
{
    /** @var PDO */
    public PDO $db;

    /** @var bool */
    private bool $tokenizerUnicode61;

    /** @var SqlIndexerSource sql query returning data to create index from */
    protected SqlIndexerSource $sqlSource;

    /**
     * Fts4Indexer constructor.
     * @param PDO $db
     * @param SqlIndexerSource $sqlSource
     */
    public function __construct(PDO $db, SqlIndexerSource $sqlSource)
    {
        $this->db = $db;
        $this->sqlSource = $sqlSource;
        $this->tokenizerUnicode61 = $this->hasTokenizerUnicode61();
        if ($this->tokenizerUnicode61 === false) {
            $this->db->sqliteCreateFunction('REMOVE_DIACRITICS', [FtsFunctions::class, 'removeDiacritics'], 1);
        }
    }

    /**
     * Check if sqlite supports using the tokenizer unicode61 in FTS4 tables.
     * @return bool
     */
    private function hasTokenizerUnicode61(): bool
    {
        $db = $this->db;
        $sql = 'CREATE VIRTUAL TABLE HasTokenizerUnicode61_fts USING fts4(Keyword, tokenize=unicode61)';
        try {
            $db->exec($sql);
            $db->exec('DROP TABLE HasTokenizerUnicode61_fts');
            $hasTokenizer = true;
        } catch (PDOException $error) {
            $hasTokenizer = false;
        }

        return $hasTokenizer;
    }

    /**
     * @return bool
     */
    public function isTokenizerUnicode61(): bool
    {
        return $this->tokenizerUnicode61;
    }
}