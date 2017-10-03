<?php
require_once __DIR__.'/../library/vendor/autoload.php';

use PhotoDatabase\Database\Database;
use WebsiteTemplate\Website;

session_start();
date_default_timezone_set('Europe/Zurich');
error_reporting(E_ERROR);

$db = new Database();
$web = new Website();