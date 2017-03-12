<?php
session_start();

date_default_timezone_set('Europe/Zurich');
error_reporting(E_ERROR);

include __DIR__.'/../../classes/class_website.php';
include __DIR__.'/../../classes/class_fotodb.php';

// note all paths should end with a slash

$db = new FotoDb('Private');

include __DIR__.'/../../classes/class_menu.php';
include __DIR__.'/../../classes/class_pagednav.php';
include __DIR__.'/../../classes/class_html.php';