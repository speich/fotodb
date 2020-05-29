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
    public static function removeDiacritics($string): string
    {
        $transliterator = Transliterator::createFromRules(
            ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;',
            \Transliterator::FORWARD
        );

        return $transliterator->transliterate($string);
    }

    /**
     * Extracts the offset of the word from the sqlite fts4 offsets string.
     * If there are multiple matches, only the offset of the first match is returned
     * Note: offset is not in characters, but bytes, e.g. a character such as ä counts as two bytes
     * @param string $offsets
     * @return int
     */
    public static function offsetWord($offsets): int
    {
        $bytes = explode(' ', $offsets);

        return $bytes[2];
    }

    /**
     * Returns the number of times a search word matched from offsets string.
     * @param $offsets
     * @return int
     */
    public static function numMatches($offsets): int
    {
        $ints = explode(' ', $offsets);

        return count($ints) / 4;
    }

    /**
     * @param $binaryData
     * @param $position
     * @return mixed
     */
    private function extractInt($binaryData, $position)
    {
        return ord(substr($binaryData, $position, $this->intSize));
    }

    private function toInt($binaryData)
    {
        return unpack('L', $binaryData)[1]; // 'L' is for: unsigned long (always 32 bit, machine byte order)
    }

    /**
     * @param $aMatchInfo
     * @return float|int
     * @throws Exception
     */
    public function rank($aMatchInfo)
    {
        $iSize = 4;
        $iPhrase = (int)0;                 // Current phrase //
        $score = (double)0.0;               // Value to return //
        /* Check that the number of arguments passed to this function is correct.
        ** If not, jump to wrong_number_args. Set aMatchinfo to point to the array
        ** of unsigned integer values returned by FTS function matchinfo. Set
        ** nPhrase to contain the number of reportable phrases in the users full-text
        ** query, and nCol to the number of columns in the table.
        */
        $aMatchInfo = (string)func_get_arg(0);
        $str = ord($aMatchInfo);
        $int = $this->toInt($aMatchInfo);
        $nPhrase = ord(substr($aMatchInfo, 0, $iSize));
        $nCol = ord(substr($aMatchInfo, $iSize, $iSize));
        if (func_num_args() > (1 + $nCol)) {
            throw new Exception("Invalid number of arguments : ".$nCol);
        }
        // Iterate through each phrase in the users query. //
        for ($iPhrase = 0; $iPhrase < $nPhrase; $iPhrase++) {
            $iCol = (int)0; // Current column //
            /* Now iterate through each column in the users query. For each column,
            ** increment the relevancy score by:
            **
            **   (<hit count> / <global hit count>) * <column weight>
            **
            ** aPhraseinfo[] points to the start of the data for phrase iPhrase. So
            ** the hit count and global hit counts for each column are found in
            ** aPhraseinfo[iCol*3] and aPhraseinfo[iCol*3+1], respectively.
            */
            $aPhraseinfo = substr($aMatchInfo, (2 + $iPhrase * $nCol * 3) * $iSize);
            for ($iCol = 0; $iCol < $nCol; $iCol++) {
                $nHitCount = ord(substr($aPhraseinfo, 3 * $iCol * $iSize, $iSize));
                $nGlobalHitCount = ord(substr($aPhraseinfo, (3 * $iCol + 1) * $iSize, $iSize));
                $weight = ($iCol < func_num_args() - 1) ? (double)func_get_arg($iCol + 1) : 0;
                if ($nHitCount > 0) {
                    $score += ((double)$nHitCount / (double)$nGlobalHitCount) * $weight;
                }
            }
        }

        return $score;
    }
}