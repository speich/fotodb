<?php

namespace PhotoDatabase\Search;

use PDO;
use function count;
use function array_slice;
use function preg_match_all;
use function strpos;

/**
 * Class Search
 */
class SearchQuery
{
    public static $maxExtractedWords = null;

    public static $minWordLength = 4;

    /**
     * Extract words and phrases from a string.
     * Treats several words as a phrase if they are wrapped in double parentheses. Parentheses are included in the array items indicating
     * that the search needs to match exactly.
     * The number of character a string has to contain to be counted as a word can be set with the parameter $minWordLength. Default is 4.
     * The length of the returned array (number of words) can be limited with the argument $maxWords. Default is 6.
     * @param string $text
     * @param null $minWordLength number of characters of a word to be counted as a word
     * @param null $maxWords maximum number of words to return
     * @return array
     */
    public static function extractWords($text, $minWordLength = null, $maxWords = null): array
    {
        $maxWords = $maxWords ?? self::$maxExtractedWords;
        $minWordLength = $minWordLength ?? self::$minWordLength;
        $pattern = '/".{'.$minWordLength.',}?"|\S{'.$minWordLength.',}/iu'; // matches whole words or several words encompassed with double quotations
        preg_match_all($pattern, $text, $words);
        if ($maxWords === null) {
            return $words[0];
        }

        return array_slice($words[0], 0, $maxWords);   // throw away words exceeding limit
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
        $len = count($words);
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