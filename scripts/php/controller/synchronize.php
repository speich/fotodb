<?php
// processes:

// synchronize exif data of all files in {folder} with database table Exif
// PUT /exif/{folder}

// synchronize xmp data of all xmp files in {folder} with database table Xmp
// PUT /xmp/{folder}

use WebsiteTemplate\Error;
use WebsiteTemplate\Controller;
use WebsiteTemplate\Header;

require_once __DIR__ . '/../../../dbprivate/inc_script.php';

$header = new Header();
$err = new Error();
$ctr = new Controller($header, $err);
$data = $ctr->getDataAsObject();
$resource = $ctr->getResources(true);

if ($data)
