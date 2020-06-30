<?php
// TODO: this code should only be available to authenticated users (->PHP)
// specially the delete function!!!!
// TODO: check all input before storing in db
use PhotoDatabase\Database\Exporter;
use PhotoDatabase\Search\ImagesIndexer;
use PhotoDatabase\Search\ImagesSearch;
use PhotoDatabase\Search\KeywordsIndexer;
use PhotoDatabase\Search\SqlImagesSource;
use PhotoDatabase\Search\SqlKeywordsSource;
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

// TODO: use rest verbs GET, POST, PUT, DELETE instead of query string
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

        case 'publish':
            echo 'exporting database...<br>';
            $exporter = new Exporter($config);
            $db = $exporter->connect();

            // update search indexes in the source before publishing it so it will be also copied to target database
            // TODO: so far only public keywords are indexed
            $sql = new SqlKeywordsSource();
            $indexer = new KeywordsIndexer($db, $sql);
            $indexer->init();
            $indexer->populate();
            echo 'created keywords index<br>';
            $sql = new SqlImagesSource();
            $indexer = new ImagesIndexer($db, $sql);
            $indexer->init();
            $indexer->populate();
            echo 'created image search index<br>';
            // copy database to target
            try {
                $exporter->publish();
                echo 'db copy successful<br>';
            } catch (RuntimeException $exception) {
                echo 'Error exporting database:<br>';
                echo $exception->getMessage();
            }

            echo 'done';
            break;

        case 'search':
            $text = $_GET['q'];

            $db = new PDO('sqlite:'.$config->paths->targetDatabase);
            $search = new ImagesSearch($db);
            $query = $search->prepareQuery($text);
            var_dump($search->search($query));
    }
}