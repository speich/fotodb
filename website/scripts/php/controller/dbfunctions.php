<?php
// TODO: this code should only be available to authenticated users (->PHP)
// specially the delete function!!!!
// TODO: check all input before storing in db
use PhotoDatabase\Database\Database;
use PhotoDatabase\Database\Exporter;
use PhotoDatabase\Database\SearchKeywords;
use WebsiteTemplate\Controller;
use WebsiteTemplate\Error;
use WebsiteTemplate\Header;


require_once __DIR__.'/../inc_script.php';
$header = new Header();
$header->setContentType('json');
$err = new Error();
$ctrl = new Controller($header, $err);
$data = $ctrl->getDataAsObject();
$data = $data ?? new stdClass();
$resources = $ctrl->getResource();
$method = $ctrl->getMethod();
$response = null;

if (property_exists($data, 'Fnc')) {
    switch ($data->Fnc) {
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

        case 'Publish':
            echo 'exporting database...<br>';
            try {
                $exporter = new Exporter($config);
                $db = $exporter->connect();
                $exporter->publish();
                echo 'db copy successful<br>';
            }
            catch (RuntimeException $exception) {
                echo 'could not export database:<br>';
                echo $exception->getMessage();
            }

            // create/update search indexes in the target database
            //$config->paths->database = '/var/www/html/fotodb/website/dbprivate/dbfiles/FotoDb.sqlite';
            //$db = new Database($config);
            //$db = $db->connect();
            $indexer = new SearchKeywords($db);
            //$destDb->sqliteCreateFunction('RANK', 'rank');
            $succ = $indexer->create();
            var_dump($indexer->db->errorInfo());
            $succ = $indexer->populate();
            $result = $indexer->search('wäld');
            var_dump($result);
            /*
                    $destDb = new PDO('sqlite:'.$config->paths->targetDatabase);
                        $indexer = new \PhotoDatabase\Database\SearchImages($destDb);
                        //$succ = $indexer->create();
                        //$succ = $indexer->populate();
                        $result = $indexer->search('jap');
                        var_dump($result);
                      */
            break;
    }
}

function rank($aMatchInfo)
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
    //$str = ord($aMatchInfo);
    //$int = $this->toInt($aMatchInfo);
    $nPhrase = ord(substr($aMatchInfo, 0, $iSize));
    $nCol = ord(substr($aMatchInfo, $iSize, $iSize));
    if (func_num_args() > (1 + $nCol)) {
        throw new Exception('Invalid number of arguments : '.$nCol);
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