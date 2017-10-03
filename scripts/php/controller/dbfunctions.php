<?php
// TODO: this code should only be available to authenticated users (->PHP)
// specially the delete function!!!!
// TODO: check all input before storing in db
use PhotoDatabase\Database\Database;
use PhotoDatabase\Database\Exporter;
use PhotoDatabase\Database\Search;

require_once '../../../dbprivate/inc_script.php';

date_default_timezone_set('Europe/Zurich');
error_reporting(E_ERROR);
header('Content-Type: text/html; charset=UTF-8');

$db = new Database();
$db->connect();

$fnc = isset($_POST['Fnc']) ? $_POST['Fnc'] : (isset($_GET['Fnc']) ? $_GET['Fnc'] : null);
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

if (isset($_GET['Fnc'])) {
    switch ($_GET['Fnc']) {
        case 'Publish':
            /*
            Using google search instead
            $indexer = new Search();
            $indexer->Connect();
            $indexer->updateIndex();
            $indexer = null;
            */
            $destDb = '/media/sf_Websites/speich.net/photo/photodb/dbfiles/photodb.sqlite';
            $destDirImg = '/media/sf_Websites/speich.net/photo/photodb/images';
            $exporter = new Exporter();
            $exporter->connect();
            $exporter->publish($destDb, $destDirImg);
            $exporter = null;
            break;
        case 'recreateThumbs':
            break;
        case 'createSearchIndex':
            // only updates local fotodb, but not speich.net
            $indexer = new Search();
            $indexer->connect();
            $indexer->updateIndex(true);
            $indexer = null;
            break;
    }
}
