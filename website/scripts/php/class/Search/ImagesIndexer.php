<?php

namespace PhotoDatabase\Search;

use PDO;


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
            DROP TABLE IF EXISTS SearchImages_fts;
            CREATE VIRTUAL TABLE SearchImages_fts USING fts4(ImgName, ImgTitle, ImgDesc, Country, Keywords, Locations, CommonNames, ScientificNames, Themes, SubjectAreas);   -- important: do not pass the row id column !
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
            INSERT INTO SearchImages_fts(rowid, ImgName, ImgTitle, ImgDesc, Country, Keywords, Locations, CommonNames, ScientificNames, Themes, SubjectAreas)
                /* note: query should return records in a way that rowId is unique for fts4 */'.
            $this->sqlSource->get().
            'COMMIT;';

        return $this->db->exec($sql);
    }
}
