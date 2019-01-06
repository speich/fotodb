<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<?php
ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);

$exifToolPath = '../library/vendor/philharvey/exiftool/exiftool';
$exifToolParams = '-G';

// Read EXIF from jpg
$img = 'test.jpg';
exec($exifToolPath.' '.$exifToolParams.' '.$img, $data);
var_dump(array_unique($data));

// Read EXIF from NEF
$img = 'test.NEF';
exec($exifToolPath.' '.$exifToolParams.' '.$img, $data);

// Read XMP
$img = 'test.xmp';
exec($exifToolPath.' '.$exifToolParams.' '.$img, $data);
sort($data);
var_dump(array_unique($data));