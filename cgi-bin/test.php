<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<?php
ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);

$ExifToolVersion = '10.24';

// Read EXIF from jpg
$img = 'test.jpg';
exec('Image-ExifTool-'.$ExifToolVersion.'/exiftool -G '.$img, $data);
var_dump(array_unique($data));

// Read EXIF from NEF
$img = 'test.NEF';
exec('Image-ExifTool-'.$ExifToolVersion.'/exiftool -G '.$img, $data);

// Read XMP
$img = 'test.xmp';
exec('Image-ExifTool-'.$ExifToolVersion.'/exiftool -G '.$img, $data);
sort($data);
var_dump(array_unique($data));