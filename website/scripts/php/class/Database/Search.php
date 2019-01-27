<?php
namespace PhotoDatabase\Database;


use Exception;

class Search
{
    private $intSize = 4;

    /**
     * @param string $matchInfo
     * @see https://gist.github.com/bohwaz/1355232
     * @return float|int
     * @throws Exception
     */
    public function rankxxx($matchInfo)
    {
        $score = 0.0;
        $matchInfo = (string) func_get_arg(0);
        $numPhrases = $this->extractInt($matchInfo, 0);
        $numCols = $this->extractInt($matchInfo, $this->intSize);

        if (func_num_args() > (1 + $numCols)) {
            throw new Exception('Invalid number of arguments: ' . $numCols);
        }

        for ($phrase = 0; $phrase < $numPhrases; $phrase++) {
            $phraseInfo = substr($matchInfo, (2 + $phrase * $numCols * 3) * $this->intSize);
            for ($col = 0; $col < $numCols; $col++) {
                $pos = 3 * $col * $this->intSize;
                $hitCount = $this->extractInt($phraseInfo, $pos);
                $pos = (3 * $col + 1) * $this->intSize;
                $globalHitCount = $this->extractInt($phraseInfo, $pos);
                $weight = ($col < func_num_args() - 1) ? (double)func_get_arg($col + 1) : 0;
                if ($hitCount > 0) {
                    $score += ((double)$hitCount / (double)$globalHitCount) * $weight;
                }
            }
        }

        return $score;
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

    private function toInt($binaryData) {
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
        $iPhrase = (int) 0;                 // Current phrase //
        $score = (double)0.0;               // Value to return //
        /* Check that the number of arguments passed to this function is correct.
        ** If not, jump to wrong_number_args. Set aMatchinfo to point to the array
        ** of unsigned integer values returned by FTS function matchinfo. Set
        ** nPhrase to contain the number of reportable phrases in the users full-text
        ** query, and nCol to the number of columns in the table.
        */
        $aMatchInfo = (string) func_get_arg(0);
        $str = ord($aMatchInfo);
        $int = $this->toInt($aMatchInfo);
        $nPhrase = ord(substr($aMatchInfo, 0, $iSize));
        $nCol = ord(substr($aMatchInfo, $iSize, $iSize));
        if (func_num_args() > (1 + $nCol))
        {
            throw new Exception("Invalid number of arguments : ".$nCol);
        }
        // Iterate through each phrase in the users query. //
        for ($iPhrase = 0; $iPhrase < $nPhrase; $iPhrase++)
        {
            $iCol = (int) 0; // Current column //
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
            for ($iCol = 0; $iCol < $nCol; $iCol++)
            {
                $nHitCount = ord(substr($aPhraseinfo, 3 * $iCol * $iSize, $iSize));
                $nGlobalHitCount = ord(substr($aPhraseinfo, (3 * $iCol + 1) * $iSize, $iSize));
                $weight = ($iCol < func_num_args() - 1) ? (double) func_get_arg($iCol + 1) : 0;
                if ($nHitCount > 0)
                {
                    $score += ((double)$nHitCount / (double)$nGlobalHitCount) * $weight;
                }
            }
        }
        return $score;
    }
}