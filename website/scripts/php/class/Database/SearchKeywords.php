<?php

namespace PhotoDatabase\Database;



use PDO;


/**
 * Class SearchKeywords
 * Provides a list of (key)words found in all relevant columns of all relevant tables to display
 * The unicode61 tokenizer is not available on the cyon.ch webshosting
 * @package PhotoDatabase\Database
 */
class SearchKeywords extends Search
{
    public $db;

    /**
     * SearchKeywords constructor.
     * @param PDO $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @return int
     */
    public function create() {
        /* note: unlike ordinary fts4 tables, contentless tables require an explicit integer docid value to be provided. External content tables are assumed to have
            a unique Id too. Therefore we cannot use a view as the external content, since that does not have a unique id. */
        $sql = 'BEGIN;
            DROP TABLE SearchKeywords_fts; 
            CREATE VIRTUAL TABLE SearchKeywords_fts USING fts4(Keyword);
            COMMIT;';

        return $this->db->exec($sql);
    }

    /**
     * Fills the virtual table with keywords.
     * @return int number of affected records
     */
    public function populate()
    {
        $sql = "BEGIN;
            INSERT INTO SearchKeywords_fts(Keyword) 
            SELECT Keyword FROM (
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
     * Remove diacritics from a string.
     * @param string $string
     * @return string
     */
    public function removeDiacritics($string): string
    {
        $transliterator = \Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;', \Transliterator::FORWARD);

        return $transliterator->transliterate($string);
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
   		$words = array_slice($words[0], 0, $maxWords); // throw away words exceeding limit

   		return $words;
   	}

    /**
     * @param string $chars
     * @return array
     */
    public function search($chars)
    {
        $chars = $this->removeDiacritics($chars);
        echo $chars;
        $chars .= '*';
        $sql = 'SELECT Keyword FROM SearchKeywords_fts
--          WHERE (SearchKeywords_fts MATCH :chars) ORDER BY RANK(matchinfo(SearchKeywords_fts), 0, 1.0, 0.5) DESC
          WHERE (SearchKeywords_fts MATCH :chars) --ORDER BY matchinfo(SearchKeywords_fts) DESC
          --ORDER BY RANK';

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':chars', $chars, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
