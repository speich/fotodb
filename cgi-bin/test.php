<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<?php
ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);

$ExifToolVersion = '10.24';

// Read EXIF from jpg
$img = '2015-09-Australia-001.jpg';
exec('Image-ExifTool-'.$ExifToolVersion.'/exiftool -G '.$img, $data);
var_dump(array_unique($data));

// Read EXIF from NEF
$img = '/media/sf_Bilder/2012-04-Florida/2012-04-Florida-001.NEF';
exec('Image-ExifTool-'.$ExifToolVersion.'/exiftool -G '.$img, $data);

// Read XMP
$img = '/media/sf_Bilder/2012-04-Florida/2012-04-Florida-001.xmp';
exec('Image-ExifTool-'.$ExifToolVersion.'/exiftool -G '.$img, $data);
sort($data);
var_dump(array_unique($data));