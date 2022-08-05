<?php

namespace PhotoDatabase\Search;

/**
 * Class SearchImages
 * @package PhotoDatabase\Database
 */
class ImagesIndexer extends Indexer
{
    /**
     * Create the database structure necessary for searching.
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
     * Fills the virtual table with searchable image info.
     * // TODO: instead of all records, only add/update new/changed records.
     */
    public function populate(): void
    {
        $tools = new IndexingTools();
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
     * Converts array to a string of column names.
     * @param callable $fnc
     * @param null $prefixed prefix names with a colon
     * @param null $postfixed postfix names with 'Prefixes'
     * @return false|string[]
     */
    private function toString(callable $fnc, $prefixed = null, $postfixed = null)
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
     * @param array $bindValues array of database columns and values
     * @param IndexingTools $tool
     * @return array
     */
    private function addPrefixes(array $bindValues, IndexingTools $tool): array
    {
        foreach ($this->sqlSource->getColPrefixes() as $name) {
            $prefixes = $bindValues[$name] === null ? null : $tool->createPrefixesFromAll($bindValues[$name], null, true);
            $bindValues[$name.'Prefixes'] = $prefixes === null ? null : implode(' ', $prefixes);
        }

        return $bindValues;
    }
}