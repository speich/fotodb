<?php

namespace PhotoDatabase\Search;

/**
 * Class SearchImages
 * @package PhotoDatabase\Database
 */
class ImagesIndexerNew extends Indexer
{
    /**
     * Create the database structure necessary for searching.
     */
    public function init()
    {
        $sql = 'BEGIN;
            DROP TABLE IF EXISTS ImagesNew_fts;
            CREATE VIRTUAL TABLE ImagesNew_fts USING fts4('.$this->getColumns().', PrefixesImgTitle, tokenize=unicode61);   -- important: do not pass the row id column !
			COMMIT;';

        return $this->db->exec($sql);
    }

    /**
     * Fills the virtual table with searchable image info.
     * // TODO: instead of all records, only add/update new/changed records.
     */
    public function populate(): void
    {
        /* note: query should return records in a way that rowId is unique for fts4 */
        $this->db->beginTransaction();
        $stmtSelect = $this->db->query($this->sqlSource->get());
        $sqlInsert = 'INSERT INTO ImagesNew_fts ('.$this->getColumns().', PrefixesImgTitle) VALUES ('.$this->getColumns(true).', :PrefixesImgTitle)';
        $stmtInsert = $this->db->prepare($sqlInsert);
        foreach ($stmtSelect as $row) {
            $prefixes = $row['ImgTitle'] === null ? null : $this->createPrefixes($row['ImgTitle'], false);
            $row['PrefixesImgTitle'] = $prefixes === null ? null : implode(' ', $prefixes);
            $stmtInsert->execute($row);
        }
        $this->db->commit();
    }

    /**
     * Splits the SQL list of columns into an array of column names.
     * @param bool $placeholders
     * @return false|string[]
     */
    private function getColumns($placeholders = false)
    {
        if ($placeholders !== false) {
            $cols = preg_filter('/^/', ':', $this->sqlSource->colNames);
        } else {
            $cols = $this->sqlSource->colNames;
        }

        return implode(', ', $cols);
    }
}