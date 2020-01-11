<?php

namespace PhotoDatabase\Search;

use PDO;


/**
 * Class SearchKeywords
 * Provides a list of (key)words found in all relevant columns of all relevant tables to display
 * @package PhotoDatabase\Database
 */
class Keywords
{
    /**
     * @var PDO
     */
    public $db;

    /**
     * Keywords constructor.
     * @param PDO $db
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->db->sqliteCreateFunction('OFFSETWORD', ['PhotoDatabase\Search\FtsFunctions', 'offsetWord']);
        $this->db->sqliteCreateFunction('NUMMATCHES', ['PhotoDatabase\Search\FtsFunctions', 'numMatches']);
    }

    /**
     * @param $text
     * @return string
     */
    public function prepareQuery($text): string
    {
        $search = '';
        $words = Search::extractWords($text);
        foreach ($words as $word) {
            $search .= $word.'*';
        }

        return $search;
    }

    /**
     * Full text search returning a list of found keywords in the database.
     * @param string $text
     * @return array keywords
     */
    public function search($text): array
    {
        $sql = 'SELECT Keyword, offsets, NUMMATCHES(offsets) nummatches FROM (
                SELECT Keyword, offsets(SearchKeywords_fts) offsets FROM SearchKeywords_fts
                WHERE (Keyword MATCH :text1)
            )
            ORDER BY CASE WHEN lower(Keyword) LIKE lower(:text2) THEN 1 ELSE 2 END,
              NUMMATCHES(offsets) DESC,
              OFFSETWORD(offsets)
            LIMIT 12 OFFSET 0';
        $query1 = $text.'*';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':text1', $query1, PDO::PARAM_STR);
        $query2 = $text.'%';
        $stmt->bindParam(':text2', $query2, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
