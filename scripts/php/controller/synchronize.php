<?php
// processes:

// synchronize exif data of all files in {folder} with database table Exif
// PUT /exif/{folder}

// synchronize xmp data of all xmp files in {folder} with database table Xmp
// PUT /xmp/{folder}

use PhotoDatabase\Database\Synchronizer;
use PhotoDatabase\FilterFilesXmp;
use PhotoDatabase\FilterSyncXmp;
use WebsiteTemplate\Error;
use WebsiteTemplate\Controller;
use WebsiteTemplate\Header;

require_once __DIR__.'/../inc_script.php';

$config = json_decode(file_get_contents('../config.json'));

$header = new Header();
$header->setContentType('json');
$err = new Error();
$ctrl = new Controller($header, $err);
$resources = $ctrl->getResource();
$method = $ctrl->getMethod();
$response = null;





$dir = 'ch/2017-10-Fenalet';

/*
 * get images to sync from database
 */
$arrImages = [];
$dirParam = $dir.'%';
$sql = "SELECT i.Id, i.ImgFolder||'/'||i.ImgName Img, x.SyncDate FROM Images i
    LEFT JOIN Xmp x ON i.id = x.ImgId";
$sql .= $dir === '' ? '' : ' WHERE i.ImgFolder LIKE :Folder';
$stmt = $db->db->prepare($sql);
if ($dir !== '') {
    $stmt->bindParam(':Folder', $dirParam);
}
$stmt->execute();
foreach ($stmt as $row) {
    // create array lookup with image path as id
    $key = explode('.', $row['Img']);
	$arrImages[$key[0]] = $row;
}


/*
 * get and filter images to sync from filesystem
 */
$dir = $config->paths->imagesOriginal.'/'.$dir;

/*$webRoot = realpath($_SERVER['DOCUMENT_ROOT'].'/'.$config->paths->imagesWebRoot);
$webRootLength = mb_strlen($webRoot);*/
$files = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
$filteredFiles = new FilterFilesXmp($files);
$filteredFiles = new FilterSyncXmp($filteredFiles, $arrImages, $config->paths->imagesOriginal);
$filteredFiles = new RecursiveIteratorIterator($filteredFiles);
foreach($filteredFiles as $fileinfo) {
    // INSERT OR REPLACE INTO Xmp
    // Database::insertXmp()
    var_dump($fileinfo);
}




/*
//$method = 'PUT';
if ($method === 'PUT' && $resources !== null) {
    $controller = array_shift($resources);
    $imgFolder = implode('/', $resources);
    // TODO: add authorization using JSON Web Tokens - jwt.io to
    $sync = new Synchronizer($db, $config->paths->imagesOriginal);
    if ($controller === 'xmp' && $sync->updateXmp($imgFolder)) {
        $response = 'synchronized xmp data of files in folder '.$imgFolder.' with database successfully';
    }

    else if ($controller === 'exif' && $sync->updateExif($imgFolder)) {
        $response = 'synchronized exif data of files in folder '.$imgFolder.' with database successfully';
    }
}

if ($response === null) {
	$ctrl->notFound = true;
}
$ctrl->printHeader();
$ctrl->printBody($response);*/