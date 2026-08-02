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
        $prefixCols = $this->toString([$this->sqlSource, 'getColPrefixes'], postfixed: true);
        $sql = 'BEGIN;
            CREATE VIRTUAL TABLE IF NOT EXISTS Images_fts USING fts4('.$cols.', '.$prefixCols.', tokenize=unicode61);   -- important: do not pass the row id column !
			COMMIT;';

        return $this->db->exec($sql);
    }

    /**
     * Fills the virtual table with searchable image info.
     * @param bool $onlyChanged populate only with new or changed images
     */
    public function populate(bool $onlyChanged = true): void
    {
        $tools = new IndexingTools();
        $cols = $this->toString([$this->sqlSource, 'getColNames']);
        $colVars = $this->toString([$this->sqlSource, 'getColNames'], true);
        $prefixCols = $this->toString([$this->sqlSource, 'getColPrefixes'], postfixed: true);
        $prefixColVars = $this->toString([$this->sqlSource, 'getColPrefixes'], true, true);

        $this->sqlSource->setOnlyChanged($onlyChanged);

        $this->db->beginTransaction();
        /* note: query should return records in a way that rowId is unique for fts4 */
        $stmtSelect = $this->db->query($this->sqlSource->get());
        $sqlDelete = 'DELETE FROM Images_fts'.($onlyChanged ? ' WHERE ImgId = :ImgId' : '');
        $sqlInsert = 'INSERT INTO Images_fts ('.$cols.', '.$prefixCols.') VALUES ('.$colVars.', '.$prefixColVars.')';
        $stmtInsert = $this->db->prepare($sqlInsert);
        $stmtDelete = $this->db->prepare($sqlDelete);
        if ($onlyChanged === false) {
            // delete all records first before re-inserting
            $stmtDelete->execute();
        }
        foreach ($stmtSelect as $row) {
            $row = $this->addPrefixes($row, $tools);
            if ($onlyChanged) {
                // delete only changed records
                $stmtDelete->execute([':ImgId' => $row['ImgId']]);
            }
            $stmtInsert->execute($row);
        }
        $this->db->commit();
    }

    /**
     * Converts an array to a string of column names.
     * @param callable $fnc
     * @param bool $prefixed prefix names with a colon
     * @param bool $postfixed postfix names with 'Prefixes'
     * @return string
     */
    private function toString(callable $fnc, bool $prefixed = false, bool $postfixed = false): string
    {
        $pattern = [];
        $replacement = [];

        if ($prefixed) {
            $pattern[] = '/^/';
            $replacement[] = ':';
        }
        if ($postfixed) {
            $pattern[] = '/$/';
            $replacement[] = 'Prefixes';
        }
        if ($prefixed || $postfixed) {
            $cols = preg_filter($pattern, $replacement, $fnc());
        } else {
            $cols = $fnc();
        }

        // preg_filter returns null on error; the null-coalescing operator prevents a TypeError in PHP 8
        return implode(', ', $cols ?? []);
    }

    /**
     * @param array $bindValues array of database columns and values
     * @param IndexingTools $tool
     * @return array
     */
    private function addPrefixes(array $bindValues, IndexingTools $tool): array
    {
        // Extract available languages dynamically (e.g., ['de', 'en'])
        $availableLangs = array_keys($tool->langTagsEnchant);

        foreach ($this->sqlSource->getColPrefixes() as $name) {
            $lang = IndexingTools::LANG_DEFAULT;
            $lowerName = strtolower($name);

            // Dynamically check if the column name ends with an active language code
            foreach ($availableLangs as $availableLang) {
                if (str_ends_with($lowerName, $availableLang)) {
                    $lang = $availableLang;
                    break;
                }
            }

            $prefixes = $bindValues[$name] === null ? null : $tool->createPrefixesFromAll($bindValues[$name], $lang, null, true);
            $bindValues[$name.'Prefixes'] = $prefixes === null ? null : implode(' ', $prefixes);
        }

        return $bindValues;
    }
}