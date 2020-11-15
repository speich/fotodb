<?php

namespace PhotoDatabase\Search;

use Exception;
use Transliterator;


/**
 * Class Search
 */
class FtsFunctions
{
    private $intSize = 4;

    /**
     * Remove diacritics from a string.
     * @param string $string
     * @return string
     */
    public static function removeDiacritics(string $string): string
    {
        $transliterator = Transliterator::createFromRules(
            ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;',
            \Transliterator::FORWARD
        );

        return $transliterator->transliterate($string);
    }

    /**
     * Remove punctuation from a string.
     * @param string $string
     * @return false|string
     */
    public static function removePunctuation(string $string)
    {

        return preg_replace("/[^\w\s]+/u", " ", $string);
    }

    /**
     * Returns the summ of the number of times all search words were matched.
     * @param string $offsets string from the FTS4 OFFSETS function
     * @return int
     */
    public static function score(string $offsets): int
    {
        $score = 0;
        $vals = explode(' ', $offsets);
        foreach ($vals as $i => $val) {
            if ($i % 4 === 1) {
                ++$score;
            }
        }

        return $score;
    }

    public static function matchInfo($col) {
        return unpack('L*', MATCHINFO($col, ));
    }
}