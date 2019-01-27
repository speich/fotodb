<?php
// TODO: this code should only be available to authenticated users (->PHP)
// specially the delete function!!!!
// TODO: check all input before storing in db
use PhotoDatabase\Database\Exporter;

require_once '../inc_script.php';



$fnc = $_POST['Fnc'] ?? $_GET['Fnc'] ?? null;
if ($fnc) {
    switch ($fnc) {
        case 'Insert':
            // insert new image and return new id
            $db->Insert($_POST['Img']);
            break;
        case 'UpdateExif':
            // Insert or update exif data of image
            $imgId = $_POST['ImgId'];
            $imgSrc = $db->getImageSrc($imgId);
            $exifData = $db->getExif($imgSrc);
            if ($db->InsertExif($imgId, $exifData)) {
                echo 'success';
            } else {
                echo 'failed';
            }
            break;
        case 'Edit':
            // return database data as xml to edit in form
            $db->Edit($_POST['ImgId']);
            break;
        case 'UpdateAll':
            // save all form data in database
            $db->UpdateAll($_POST['XmlData']);
            break;
        case 'Del':
            // delete db data
            $db->Delete($_POST['ImgId']);
            break;
        case 'FldLoadData':
            // load specific form data, e.g. locations in a certain country or a scientific name
            $db->LoadData();
            break;
    }
}

if (isset($_GET['Fnc']) && $_GET['Fnc'] === 'Publish') {
        $exporter = new Exporter($config);
        $exporter->connect();
        $exporter->publish();

        // create/update search indexes in the target database
        /*
            $indexer = new \PhotoDatabase\Database\SearchKeywords($destDb);
            //$destDb->sqliteCreateFunction('RANK', 'rank');
            $succ = $indexer->create();
            var_dump($indexer->db->errorInfo());
            $succ = $indexer->populate();
            $result = $indexer->search('krah');
            var_dump($result);

        $destDb = new PDO('sqlite:'.$config->paths->targetDatabase);
            $indexer = new \PhotoDatabase\Database\SearchImages($destDb);
            //$succ = $indexer->create();
            //$succ = $indexer->populate();
            $result = $indexer->search('jap');
            var_dump($result);
          */
}

function rank($aMatchInfo)
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
        //$str = ord($aMatchInfo);
        //$int = $this->toInt($aMatchInfo);
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