<?php

namespace PhotoDatabase\Search;

use Transliterator;


/**
 * Class Search
 */
class FtsFunctions
{

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

        return $transliterator === null ? $string : $transliterator->transliterate($string);
    }

    /**
     * Remove punctuation from a string.
     * @param string $string
     * @return string
     */
    public static function removePunctuation(string $string): string
    {
        $str = preg_replace("/[^\w\s]+/u", ' ', $string);

        return $str ?? $string;
    }

    /**
     * Calculates term frequency - inverse document frequency
     * Expects the binary output from MATCHINFO with format parameters 'xncp';
     * @param string $matchinfoOut binary string from matchinfo
     * @return float|int
     */
    public static function tfIdf(string $matchinfoOut)
    {
        $arrInt32 = unpack('L*', $matchinfoOut);

        // note: returned array starts at index 1, not 0
        $numPhrases = array_pop($arrInt32);
        $numCols = array_pop($arrInt32);
        $numRows = array_pop($arrInt32);

        $score = 0;
        $tf = 0;
        foreach ($arrInt32 as $i => $int) {
            $remainder = ($i - 1) % 3;
            if ($remainder === 0) {
                $tf = $int;   // term frequency
            } elseif ($remainder === 2) {
                // $int = document frequency
                $idf = $int > 0 ? log10($numCols * $numRows / $int) : 0;
                $score += $tf * $idf;
            }
        }

        return $score;
    }

    /**
     * Calculates term frequency - inverse document frequency weighted by column.
     * Expects the binary output from MATCHINFO with format parameters 'xncp';
     * @param string $matchinfoOut binary string from matchinfo
     * @param string $colWeights comma separated column weights
     * @return float|int
     */
    public static function tfIdfWeighted(string $matchinfoOut, string $colWeights)
    {
        $arrInt32 = unpack('L*', $matchinfoOut);
        /** @var array $weights */
        $weights = explode(',', $colWeights);

        $numPhrases = array_pop($arrInt32);
        $numCols = array_pop($arrInt32);
        $numRows = array_pop($arrInt32);
        $score = 0;
        $tf = 0;
        foreach ($arrInt32 as $i => $int) {
            $j = $i - 1;
            $z = $j % ($numCols * 3);
            /** @var int $colIdx */
            $colIdx = ($z - $z % 3) / 3;
            $colWeight = $weights[$colIdx];
            $remainder = $j % 3;
            if ($remainder === 0) {
                $tf = $int;   // term frequency
            } elseif ($remainder === 2) {
                $df = $int;   // document frequency
                $idf = $df > 0 ? log10($numCols * $numRows / $df) : 0;
                $score += $tf * $idf * $colWeight;
            }
        }

        return $score;
    }
}