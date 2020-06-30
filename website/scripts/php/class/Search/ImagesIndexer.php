<?php

namespace PhotoDatabase\Search;

/**
 * Class SearchImages
 * @package PhotoDatabase\Database
 */
class ImagesIndexer extends Indexer
{
    /**
     * Create the database tables necessary for searching.
     */
    public function init()
    {
        $sql = 'BEGIN;
            DROP TABLE IF EXISTS Images_fts;
            CREATE VIRTUAL TABLE Images_fts USING fts4(ImgId, Keyword, Weight, Language, tokenize=unicode61);   -- important: do not pass the row id column !
			COMMIT;';

        return $this->db->exec($sql);
    }

    /**
     * Fills the virtual table with searchable image info.
     * @return false|int
     */
    public function populate()
    {
        $sql = 'BEGIN;
            /* note: query should return records in a way that rowId is unique for fts4 */'.'
            INSERT INTO Images_fts(ImgId, Keyword, Weight, Language)'.
            $this->sqlSource->get().
            'COMMIT;';

        return $this->db->exec($sql);
    }

    /**
     * Splits the SQL list of columns into an array of column names.
     * @return false|string[]
     */
    private function getColumns()
    {
        $cols = $this->sqlSource->getList();
        $cols = preg_replace('/\s+/', '', $cols);
        $cols = explode(',', $cols);

        return $cols;
    }

}
