<?php
namespace PhotoDatabase\Search;


use Exception;
use PDO;
use Transliterator;


/**
 * Class Search
 * @package PhotoDatabase\Database
 */
class Search
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

}