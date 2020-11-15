<?php

namespace PhotoDatabase\Search;

use PDO;

/**
 * Class SearchKeywords
 * Provides a list of (key)words found in all relevant columns of all relevant tables to display
 * @package PhotoDatabase\Database
 */
class ImagesSearch
{
    /** @var PDO */
    public $db;

    /**
     * Keywords constructor.
     * @param PDO $db
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->db->sqliteCreateFunction('SCORE', [FtsFunctions::class, 'score']);
    }

    /**
     * @param $text
     * @return string
     */
    public function prepareQuery($text): string
    {

        /* TODO
        if (nounicode) {
            $text = FtsFunctions::removeDiacritics($text);
        }
        */
        $words = SearchQuery::extractWords($text);

        return SearchQuery::createQuery($words);
    }

    /**
     * Full text search returning a list of found keywords in the database.
     * @param string $text
     * @return array keywords
     */
    public function search(string $text): array
    {
        // TODO: non-unicode version
        $sql = "SELECT * FROM (
            SELECT ImgId, SUM(SCORE(offsets) * Weight) Rank FROM (
                SELECT ImgId, OFFSETS(Images_fts) offsets, Weight FROM Images_fts
                WHERE (Keyword MATCH :text)
                LIMIT -1 OFFSET 0 -- otherwise will get an error because of subquery flattening
            )
            GROUP BY ImgId            
            ORDER BY Rank DESC)
            INNER JOIN Images I ON I.Id = ImgId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':text', $text, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
