<?php
// processes:

// synchronize exif data of all files in {folder} with database table Exif
// PUT /exif/{folder}

// synchronize xmp data of all xmp files in {folder} with database table Xmp
// PUT /xmp/{folder}

use PhotoDatabase\Database\Synchronizer;
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


// directory to sync from image root
//$dir = 'ch/2017-10-Fenalet';
$dir = '2017-03-Florida';
$sync = new Synchronizer($db, $config);
$sync->updateXmp($dir);





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