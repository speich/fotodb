<?php

namespace PhotoDatabase\Search;

use PDO;
use PDOException;
use PhotoDatabase\Sql\Sql;


/**
 * Class Indexer
 * @package PhotoDatabase\Search
 */
abstract class Indexer implements Fts4Indexer
{
    /** @var PDO */
    public $db;

    /** @var bool */
    private $tokenizerUnicode61;

    /** @var Sql sql query returning data to create index from */
    protected $sqlSource;

    /**
     * Fts4Indexer constructor.
     * @param PDO $db
     */
    public function __construct(PDO $db, Sql $sqlSource)
    {
        $this->db = $db;
        $this->sqlSource = $sqlSource;
        $this->tokenizerUnicode61 = $this->hasTokenizerUnicode61();
        if ($this->tokenizerUnicode61 === false) {
            $this->db->sqliteCreateFunction('REMOVE_DIACRITICS', ['PhotoDatabase\Search\FtsFunctions', 'removeDiacritics']);
        }
    }

    /**
     * Check if sqlite supports using the tokenizer unicode61 in FTS4 tables.
     * @return bool
     */
    private function hasTokenizerUnicode61()
    {
        $db = $this->db;
        $sql = 'CREATE VIRTUAL TABLE Test_fts USING fts4(Keyword, tokenize=unicode61)';
        try {
            $db->exec($sql);
            $db->exec('DROP TABLE Test_fts');
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