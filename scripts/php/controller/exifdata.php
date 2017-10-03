<?php
include_once 'inc_script.php';
include __DIR__.'/../../classes/ExifService.php';

header('Content-Type: text/html; charset=utf-8');

// all paths end should end with a slash
$path = $db->GetPath('Img');

// path to original image files, e.g. Nikon NEF
// remember: has to be reachable by web server (also on virtual machine -> map into virtual machine too)
$pathImgOrig = '/media/sf_Bilder/';
if ($_SERVER['HTTP_HOST'] == 'photoxplorer.net' || $_SERVER['HTTP_HOST'] == 'www.photoxplorer.net') {
	$pathImgOrig = '/images/';
}

if (isset($_GET['Image'])) {
	$exif = new \photoXplorer\ExifService();
	$exif->setPaths($pathImgOrig, $path);
	$arr = $exif->read($_GET['Image']);
	$exif->render($arr);
}