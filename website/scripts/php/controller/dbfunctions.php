<?php
// TODO: this code should only be available to authenticated users (->PHP)
// specially the delete function!!!!
// TODO: check all input before storing in db
use PhotoDatabase\Database\Database;
use PhotoDatabase\Database\Exporter;
use PhotoDatabase\Search\Keywords;
use PhotoDatabase\Search\KeywordsIndexer;
use PhotoDatabase\Search\KeywordsIndexerNoUnicode;
use PhotoDatabase\Search\KeywordsNoUnicode;
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

set_time_limit(180);

if (property_exists($data, 'Fnc')) {
    switch ($data->Fnc) {
        case 'Insert':
            // insert new image and return new id
            $db->insert($_POST['Img']);
            break;
        case 'UpdateExif':
            // Insert or update exif data of image
            $imgId = $_POST['ImgId'];
            $imgSrc = $db->getImageSrc($imgId);
            $exifData = $db->getExif($imgSrc);
            if ($db->insertExif($imgId, $exifData)) {
                echo 'success';
            } else {
                echo 'failed';
            }
            break;
        case 'Edit':
            // return database data as xml to edit in form
            $db->edit($_POST['ImgId']);
            break;
        case 'UpdateAll':
            // save all form data in database
            $db->updateAll($_POST['XmlData']);
            break;
        case 'Del':
            // delete db data
            $db->delete($_POST['ImgId']);
            break;
        case 'FldLoadData':
            // load specific form data, e.g. locations in a certain country or a scientific name
            $db->loadData();
            break;

        case 'Publish':
            echo 'exporting database...<br>';
            $exporter = new Exporter($config);
            $db = $exporter->connect();
            try {
                $exporter->publish();
                echo 'db copy successful<br>';
            } catch (RuntimeException $exception) {
                echo 'Error exporting database:<br>';
                echo $exception->getMessage();
            }
            flush();

            // create/update search indexes in the target database
            //$indexer = new KeywordsIndexer($db);
            $indexer = new KeywordsIndexerNoUnicode($db);
            $indexer->init();
            $indexer->populate();
            flush();
            echo 'done';
            break;

        case 'search':
            $db = new PDO('sqlite:'.$config->paths->database);
            $search = new KeywordsNoUnicode($db);
            $query = $search->prepareQuery('Wälder');
            var_dump($search->search($query));
    }
}