<?php

use PhotoDatabase\Database\Database;
use WebsiteTemplate\Website;


date_default_timezone_set('Europe/Zurich');
error_reporting(E_ERROR);

require_once __DIR__.'/../../library/vendor/autoload.php';

$file = file_get_contents(__DIR__.'/config.json');
$config = json_decode($file, false);
$db = new Database($config);
$db->connect();

$web = new Website();