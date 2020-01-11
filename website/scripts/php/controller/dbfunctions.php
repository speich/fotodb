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
            /*
                try {
                    $exporter->publish();
                    echo 'db copy successful<br>';
                } catch (RuntimeException $exception) {
                    echo 'could not export database:<br>';
                    echo $exception->getMessage();
                }
    */
            flush();

            // create/update search indexes in the target database
            //$config->paths->database = '/var/www/html/fotodb/website/dbprivate/dbfiles/FotoDb.sqlite';
            //$db = new Database($config);
            //$destDb = $db;  // only temp for testing
            //$destDb = $db->connect();
            //$destDb->sqliteCreateFunction('RANK', 'rank');

        /*
            $indexer = new KeywordsIndexer($db);
            $succ = $indexer->init();
            var_dump($indexer->db->errorInfo());
            $succ = $indexer->populate();
            flush();

            $indexer = new KeywordsIndexerNoUnicode($db);
            $succ = $indexer->init();
            var_dump($indexer->db->errorInfo());
            $succ = $indexer->populate();
            flush();
*/
            $search = new Keywords($db);
            //$query = $search->prepareQuery($_GET['q']);
            var_dump($search->search($_GET['q']));
/*
            $search = new KeywordsNoUnicode($db);
            $query = $search->prepareQuery('schnä');
            var_dump($search->search($query));
*/
            // $destDb = new PDO('sqlite:'.$config->paths->targetDatabase);
            //$destDb = $db;  // only temp for testing
            //$destDb = $db->connect();
            //$indexer = new SearchImages($destDb);
            //$indexer->create();
            //$indexer->populate();
            //$result = $indexer->search('turdus');
            //var_dump($result);
            echo 'done';
            break;
    }
}