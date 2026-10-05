<?php

namespace PhotoDatabase\Search;

/**
 * Handles the creation and population of the full-text search (FTS4) index for images.
 *
 * Maps structural database content into a virtual table optimized for fast text querying.
 *
 * @package PhotoDatabase\Database
 */
class ImagesIndexer extends Indexer
{
    /**
     * Creates the virtual database table structure necessary for FTS4 searching.
     *
     * Uses the unicode61 tokenizer for better diacritic handling if supported.
     *
     * @return bool|int Returns the number of affected rows on success, or false on failure.
     */
    public function init(): bool|int
    {
        $cols = $this->toString([$this->sqlSource, 'getColNames']);
        $prefixCols = $this->toString([$this->sqlSource, 'getColPrefixes'], null, true);
        $sql = 'BEGIN;
            CREATE VIRTUAL TABLE IF NOT EXISTS Images_fts USING fts4('.$cols.', '.$prefixCols.', tokenize=unicode61);   -- important: do not pass the row id column !
			COMMIT;';

        return $this->db->exec($sql);
    }

    /**
     * Fills the virtual FTS4 table with searchable image data.
     *
     * Iterates through all images from the source query, generates the necessary
     * word prefixes, and updates the index via a DELETE/INSERT transaction to
     * ensure clean token updates.
     *
     * @todo Instead of processing all records, only add/update new/changed records.
     *
     * @return void
     */
    public function populate(): void
    {
        $tools = [
            'de' => new IndexingTools('de_CH', 'de-ch-1901'),
            'en' => new IndexingTools('en_US', 'en-us')
        ];

        $cols = $this->toString([$this->sqlSource, 'getColNames']);
        $colVars = $this->toString([$this->sqlSource, 'getColNames'], true);
        $prefixCols = $this->toString([$this->sqlSource, 'getColPrefixes'], null, true);
        $prefixColVars = $this->toString([$this->sqlSource, 'getColPrefixes'], true, true);

        $this->db->beginTransaction();
        $stmtSelect = $this->db->query($this->sqlSource->get());

        /* note: query should return records in a way that rowId is unique for fts4 */
        $sqlDelete = 'DELETE FROM Images_fts WHERE ImgId = :ImgId';
        $sqlInsert = 'INSERT INTO Images_fts ('.$cols.', '.$prefixCols.') VALUES ('.$colVars.', '.$prefixColVars.')';
        $stmtInsert = $this->db->prepare($sqlInsert);
        $stmtDelete = $this->db->prepare($sqlDelete);
        foreach ($stmtSelect as $row) {
            $row = $this->addPrefixes($row, $tools);
            $stmtDelete->execute([':ImgId' => $row['ImgId']]);
            $stmtInsert->execute($row);
        }
        $this->db->commit();
    }

    /**
     * Applies the correct language dictionary based on the column name.
     */
    protected function addPrefixes(array $bindValues, array $tools): array
    {
        foreach ($this->sqlSource->getColPrefixes() as $name) {
            if ($bindValues[$name] === null) {
                $bindValues[$name.'Prefixes'] = null;
                continue;
            }

            // Route to English if the column ends with "En" (e.g., KeywordsEn, CommonNamesEn).
            // Default to German for all others (KeywordsDe, ImgTitle, ImgDesc).
            $lang = str_ends_with($name, 'En') ? 'en' : 'de';
            $activeTool = $tools[$lang] ?? $tools['de'];

            $prefixes = $activeTool->createPrefixesFromAll($bindValues[$name], null, true);
            $bindValues[$name.'Prefixes'] = $prefixes === null ? null : implode(' ', $prefixes);
        }

        return $bindValues;
    }


}