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

$header = new Header();
$header->setContentType('json');
$err = new Error();
$ctrl = new Controller($header, $err);
$resources = $ctrl->getResources();
$method = $ctrl->getMethod();
$response = null;



if ($method === 'PUT' && !is_null($resources)) {
    $controller = array_shift($resources);
    $imgFolder = implode('/', $resources);

    // TODO: add authorization using JSON Web Tokens - jwt.io to
    $sync = new Synchronizer($db);
    if ($controller === 'xmp' && $sync->syncXmp($imgFolder)) {
        $response = 'synchronized xmp data of files in folder '.$imgFolder.' with database successfully';
    }

    else if ($controller === 'exif' && $sync->syncExif($imgFolder)) {
        $response = 'synchronized exif data of files in folder '.$imgFolder.' with database successfully';
    }

}

if (is_null($response)) {
	$ctrl->notFound = true;
}
$ctrl->printHeader();
$ctrl->printBody($response);