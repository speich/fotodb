<?php

use PhotoDatabase\Database;

session_start();

date_default_timezone_set('Europe/Zurich');
error_reporting(E_ERROR);



$db = new Database();
$web = new Website();