<?php
// Executes the ExifTool by Phil Harvey
// and returns the data as an alphabetically sorted array
// http://owl.phy.queensu.ca/~phil/exiftool/
ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);

$ExifToolVersion = '10.24';
$lang = isset($_GET['lang']) ? filter_input(INPUT_GET, 'lang', FILTER_SANITIZE_MAGIC_QUOTES) : 'en';
$tool = __DIR__.'/../../cgi-bin/Image-ExifTool-'.$ExifToolVersion.'/exiftool -c "%+.8f" -G -lang '.$lang;


$img = '/media/sf_Bilder/2016-05-Ungarn/_DSC0004.NEF';
exec($tool.' '.$img, $data);

$img = '/media/sf_Bilder/2016-05-Ungarn/_DSC0004.xmp';
exec($tool.' '.$img, $data);
sort($data);



header('Content-Type: text/html; charset=utf-8');

var_dump(array_unique($data));