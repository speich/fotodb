<?php
// Executes the ExifTool by Phil Harvey
// and returns the data as an alphabetically sorted array
// http://owl.phy.queensu.ca/~phil/exiftool/
//ini_set('xdebug.var_display_max_depth', -1);
//ini_set('xdebug.var_display_max_children', -1);
//ini_set('xdebug.var_display_max_data', -1);
use PhotoDatabase\ExifService;

require_once '../inc_script.php';
$config = json_decode(file_get_contents('../config.json'));

$lang = isset($_GET['lang']) ? filter_input(INPUT_GET, 'lang', FILTER_SANITIZE_MAGIC_QUOTES) : 'en';
$exif = new ExifService($config->paths->exifTool, $lang);

// query string should only contain path information below image root, e.g. below /dbprivate/images/
// which will be mapped into /media/sf_Bilder/
$img = '/media/sf_Bilder'.$_GET['img'];
$data = $exif->getData($img);

header('Content-Type: text/html; charset=utf-8');
echo $exif->render($data);