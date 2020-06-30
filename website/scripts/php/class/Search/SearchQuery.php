<?php

namespace PhotoDatabase\Search;

use PDO;


/**
 * Class Search
 * @package PhotoDatabase\Database
 */
class SearchQuery
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
     * Extract words and phrases from a string.
     * Treats several words as a phrase if they are wrapped in double parentheses. Parentheses are included in the array items indicating
     * that the search needs to match exactly.
     * The length of the returned array (number of words) can be limited with the argument $maxWords. Default is 6.
     * The number of character a string has to contain to be counted as a word can be set with the parameter $minWordLength. Default is 3.
     * @param string $text
     * @param int $maxWords maximum number of words to return
     * @param int $minWordLength length of a word to be counted as a word
     * @return array
     */
    public static function extractWords($text, $maxWords = null, $minWordLength = null): array
    {
        $maxWords = $maxWords ?? 6;
        $minWordLength = $minWordLength ?? 3;
        $pattern = '/".{'.$minWordLength.',}?"|\S{'.$minWordLength.',}/iu'; // matches whole words or several words encompassed with double quotations
        preg_match_all($pattern, $text, $words);
        $words = \array_slice($words[0], 0, $maxWords); // throw away words exceeding limit

        return $words;
    }

    /**
     * Creates the search query.
     * Postfixes each word with an asterix if an exact match is not wanted (e.g. wrapped in parenthesis)
     * @param $words
     * @return string
     */
    public static function createQuery($words): string
    {
        $search = '';
        $len = \count($words);
        foreach ($words as $i => $val) {
            if (strpos($val, '"') !== false) {
                $search .= $val;
            } else {
                $search .= $val.'*';
            }
            $search .= $i < $len - 1 ? ' OR ' : '';
        }

        return $search;
    }
}