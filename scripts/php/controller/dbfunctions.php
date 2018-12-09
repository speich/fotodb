<?php
// TODO: this code should only be available to authenticated users (->PHP)
// specially the delete function!!!!
// TODO: check all input before storing in db
use PhotoDatabase\Database\Exporter;
use PhotoDatabase\Database\SearchImages;
use PhotoDatabase\Database\SearchKeywords;


require_once '../inc_script.php';



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
            $destDb = '/media/sf_Websites/speich.net/photo/photodb/dbfiles/photodb.sqlite';
            $destDirImg = '/media/sf_Websites/speich.net/photo/photodb/images';
            $exporter = new Exporter($config);
            $exporter->connect();
            $exporter->publish($destDb, $destDirImg);
            $db = new PDO('sqlite:'.$destDb);
            $index = new SearchKeywords($db);
            $index->createStructure();
            $index->populate();
            $index = new SearchImages($db);
            $index->createStructure();
            $index->populate();
            break;
    }
}
