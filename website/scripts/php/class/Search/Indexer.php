<?php

namespace PhotoDatabase\Search;

use PDO;
use PDOException;


/**
 * Base class for full-text search indexers.
 *
 * Provides common functionality for interacting with the SQLite FTS4 engine,
 * managing the tokenizer support, and formatting SQL column strings.
 *
 * @package PhotoDatabase\Search
 */
abstract class Indexer implements Fts4Indexer
{
    /** @var PDO The active database connection */
    public PDO $db;

    /** @var bool Indicates if the SQLite environment supports the unicode61 tokenizer */
    private bool $tokenizerUnicode61;

    /** @var SqlIndexerSource The SQL query builder returning data to index */
    protected SqlIndexerSource $sqlSource;

    /**
     * Initializes the indexer and verifies tokenizer support.
     *
     * If the unicode61 tokenizer is unavailable, it registers a fallback
     * REMOVE_DIACRITICS SQLite function.
     *
     * @param PDO $db The active database connection instance.
     * @param SqlIndexerSource $sqlSource The source mapping for indexing columns.
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
     * Checks if the SQLite engine supports using the 'unicode61' tokenizer in FTS4 tables.
     *
     * @return bool True if unicode61 is supported, false otherwise.
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
     * Returns whether the unicode61 tokenizer is active.
     *
     * @return bool
     */
    public function isTokenizerUnicode61(): bool
    {
        return $this->tokenizerUnicode61;
    }

    /**
     * Formats an array of column names into a comma-separated string.
     *
     * Can optionally append or prepend specific syntax markers (like PDO binding colons).
     *
     * @param callable $fnc Callable returning the array of column names.
     * @param bool|null $prefixed If true, prefixes all column names with a colon (':').
     * @param bool|null $postfixed If true, postfixes all column names with 'Prefixes'.
     * @return string|false The formatted string, or false if the operation fails.
     */
    protected function toString(callable $fnc, $prefixed = null, $postfixed = null): string|false
    {
        $pattern = [];
        $replacement = [];
        if ($prefixed === true) {
            $pattern[] = '/^/';
            $replacement[] = ':';
        }
        if ($postfixed === true) {
            $pattern[] = '/$/';
            $replacement[] = 'Prefixes';
        }
        if ($prefixed !== null || $postfixed !== null) {
            $cols = preg_filter($pattern, $replacement, $fnc());
        } else {
            $cols = $fnc();
        }

        return implode(', ', $cols);
    }

    /**
     * Appends generated word prefixes to the database binding array for specified columns.
     *
     * @param array $bindValues The array of database columns and values for a single row.
     * @param IndexingTools $tool The linguistic toolset used to generate the prefixes.
     * @return array The updated array containing both original values and their generated prefixes.
     */
    protected function addPrefixes(array $bindValues, IndexingTools $tool): array
    {
        foreach ($this->sqlSource->getColPrefixes() as $name) {
            $prefixes = $bindValues[$name] === null ? null : $tool->createPrefixesFromAll($bindValues[$name], null, true);
            $bindValues[$name.'Prefixes'] = $prefixes === null ? null : implode(' ', $prefixes);
        }

        return $bindValues;
    }

}