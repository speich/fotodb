<?php

namespace PhotoDatabase\Search;

/**
 * Class KeywordsIndexer
 * Creates a language-aware fulltext search dictionary for autosuggest.
 */
class KeywordsIndexer extends Indexer
{
    /**
     * Create the virtual table for the dictionary, now including Language.
     */
    public function init(): int
    {
        $sql = 'BEGIN;
            CREATE VIRTUAL TABLE IF NOT EXISTS Keywords_fts USING fts4(Keyword, Lang, KeywordPrefixes, tokenize=unicode61);
            COMMIT;';

        return $this->db->exec($sql);
    }

    /**
     * Fills the virtual table with unique keywords.
     */
    public function populate(): void
    {
        $tools = [
            'de' => new IndexingTools('de_CH', 'de-ch-1901'),
            'en' => new IndexingTools('en_US', 'en-us')
        ];

        $this->db->beginTransaction();

        // Clear the old dictionary completely
        $this->db->exec('DELETE FROM Keywords_fts');

        $stmtSelect = $this->db->query($this->sqlSource->get());

        // The parent Indexer class will dynamically generate:
        // INSERT INTO Keywords_fts (Keyword, Language, KeywordPrefixes) VALUES (:Keyword, :Language, :KeywordPrefixes)
        $cols = $this->toString([$this->sqlSource, 'getColNames']);
        $colVars = $this->toString([$this->sqlSource, 'getColNames'], true);
        $prefixCols = $this->toString([$this->sqlSource, 'getColPrefixes'], null, true);
        $prefixColVars = $this->toString([$this->sqlSource, 'getColPrefixes'], true, true);

        $sqlInsert = 'INSERT INTO Keywords_fts (' . $cols . ', ' . $prefixCols . ') VALUES (' . $colVars . ', ' . $prefixColVars . ')';
        $stmtInsert = $this->db->prepare($sqlInsert);

        foreach ($stmtSelect as $row) {
             $row = $this->addPrefixes($row, $tools);
            $stmtInsert->execute($row);
        }

        $this->db->commit();
    }

    /**
     * Applies the correct language dictionary based on the row's Language column.
     */
    protected function addPrefixes(array $bindValues, array $tools): array
    {
        // Route to the language specified in the database row
        $lang = $bindValues['Lang'] ?? 'de';
        $activeTool = $tools[$lang] ?? $tools['de'];

        foreach ($this->sqlSource->getColPrefixes() as $name) {
            if ($bindValues[$name] === null) {
                $bindValues[$name.'Prefixes'] = null;
                continue;
            }

            $prefixes = $activeTool->createPrefixesFromAll($bindValues[$name], null, true);
            $bindValues[$name.'Prefixes'] = $prefixes === null ? null : implode(' ', $prefixes);
        }

        return $bindValues;
    }
}