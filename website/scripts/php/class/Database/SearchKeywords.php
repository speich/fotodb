<?php

namespace PhotoDatabase\Database;

use PDO;


/**
 * Class SearchKeywords
 * Provides a list of (key)words found in all relevant columns of all relevant tables to display
 * @package PhotoDatabase\Database
 */
class SearchKeywords extends Search
{
    /**
     * @return int
     */
    public function create(): int
    {
        /* note: unlike ordinary fts4 tables, contentless tables require an explicit integer docid value to be provided. External content tables are assumed to have
            a unique Id too. Therefore we cannot use a view as the external content, since that does not have a unique id. */
        $sql = 'BEGIN;
            DROP TABLE SearchKeywords_fts; 
            CREATE VIRTUAL TABLE SearchKeywords_fts USING fts4(KeywordMod, KeywordOrig);
            COMMIT;';

        return $this->db->exec($sql);
    }

    /**
     * Fills the virtual table with keywords.
     * Note: automatically removes diacritics
     * @return int number of affected records
     */
    public function populate(): int
    {
        $sql = "BEGIN;
            INSERT INTO SearchKeywords_fts(KeywordMod, KeywordOrig) 
            SELECT REMOVE_DIACRITICS(Keyword), Keyword FROM (
                SELECT ImgName Keyword FROM Images WHERE Public = 1
                UNION
                SELECT ImgTitle FROM Images WHERE Public = 1
                UNION
                SELECT ImgDesc FROM Images WHERE Public = 1
                UNION
                SELECT c.NameDe FROM Images i
                INNER JOIN Countries c ON i.CountryId = c.Id
                WHERE i.Public = 1
                UNION
                SELECT k.Name FROM Images i
                INNER JOIN Images_Keywords ik ON i.Id = ik.ImgId
                INNER JOIN Keywords k ON ik.KeywordId = k.Id
                WHERE i.Public = 1
                UNION
                SELECT l.Name FROM Images i
                INNER JOIN Images_Locations il ON il.ImgId = i.Id
                INNER JOIN Locations l ON il.LocationId = l.Id
                WHERE i.Public = 1
                UNION
                SELECT s.NameDe FROM Images i
                INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
                INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
                WHERE i.Public = 1
                UNION
                SELECT s.NameLa FROM Images i
                INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
                INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
                WHERE i.Public = 1
                UNION
                SELECT t.NameDe FROM Images i
                INNER JOIN Images_Themes it ON i.Id = it.ImgId
                INNER JOIN Themes t ON it.ThemeId = t.Id
                WHERE i.Public = 1
                UNION
                SELECT a.NameDe FROM Images i
                INNER JOIN Images_Themes it ON i.Id = it.ImgId
                INNER JOIN Themes t ON it.ThemeId = t.Id
                INNER JOIN SubjectAreas a ON t.SubjectAreaId = a.Id
                WHERE i.Public = 1
            )
            WHERE Keyword != '';
            COMMIT;";

        return $this->db->exec($sql);
    }

    /**
     * Extract words and phrases from query to search for.
     * Treat several words as a phrase if they are wrapped in double parentheses. Parentheses are included in the array items indicating
     * that the search needs to match exactly.
     * The length of the returned array (number of words) can be limited with the argument $maxWords. Default is 4.
     * The number of character a string has to contain to be counted as a word can be set with the parameter $minWordLength.
     * @param string $query
     * @param int $maxWords
     * @param int $minWordLength
     * @return array
     */
    public static function extractWords($query, $maxWords = 6, $minWordLength = 3): array
    {
        $pattern = '/".{'.$minWordLength.',}?"|\S{'.$minWordLength.',}/iu'; // matches whole words or several words encompassed with double quotations
        preg_match_all($pattern, $query, $words);
        $words = \array_slice($words[0], 0, $maxWords); // throw away words exceeding limit

        return $words;
    }

    /**
     * Creates the search query from the input
     * Removes diacritics and appends an asterix to each word. Multiple words enclosed in parenthesis are treated as on single word
     * @param $text
     * @return string
     */
    public function prepareQuery($text): string
    {
        $search = '';
        $text = $this->removeDiacritics($text);
        $words = self::extractWords($text);
        foreach ($words as $word) {
            $search .= $word.'*';
        }

        return $search;
    }

    /**
     * Full text search
     * @param string $text
     * @return array
     */
    public function search($text): array
    {
        $sql = 'SELECT rowid, KeywordOrig FROM SearchKeywords_fts
--          WHERE (SearchKeywords_fts MATCH :chars) ORDER BY RANK(matchinfo(SearchKeywords_fts), 0, 1.0, 0.5) DESC
          WHERE (SearchKeywords_fts MATCH :text) --ORDER BY matchinfo(SearchKeywords_fts) DESC
          --ORDER BY RANK';

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':text', $text, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
