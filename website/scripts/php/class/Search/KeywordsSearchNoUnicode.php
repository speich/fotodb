<?php

namespace PhotoDatabase\Search;

use PDO;


/**
 * Class SearchKeywords
 * Provides a list of (key)words found in all relevant columns of all relevant tables to display
 * @package PhotoDatabase\Database
 */
class KeywordsSearchNoUnicode
{
    /**
     * Keywords constructor.
     * @param PDO $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Create a string of search words to be used in a KeywordsNoUnicode::search().
     * Appends to each word the suffix '*' to be use in a sqlite fts4 MATCH query.
     * @param string $text
     * @return string
     */
    public function prepareQuery(string $text): string
    {
        $search = '';
        $text = FtsFunctions::removeDiacritics($text);
        $words = SearchQuery::extractWords($text);
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
    public function search(string $text): array
    {
        $sql = 'SELECT rowid, KeywordOrig, KeywordMod FROM SearchKeywords_fts
--          WHERE (SearchKeywords_fts MATCH :chars) ORDER BY RANK(matchinfo(SearchKeywords_fts), 0, 1.0, 0.5) DESC
          WHERE (KeywordMod MATCH :text) --ORDER BY matchinfo(SearchKeywords_fts) DESC
          --ORDER BY RANK';

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':text', $text, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}