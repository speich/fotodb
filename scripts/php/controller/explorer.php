<?php

use PhotoDatabase\Database\Database;
use PhotoDatabase\Database\Preferences;
use PhotoDatabase\FileExplorer;
use WebsiteTemplate\Website;

require_once '../inc_script.php';

// note: all paths should end with a slash
$prefs = new Preferences();
$fs = new FileExplorer($db);
$fs->setTopDir($config->paths->image);

if (isset($_GET['Type'])) {
	if (isset($_GET['Dir'])) {
		$path = preg_replace("/[\"'<>;:]/", "", $_GET['Dir']);
	}
	else {
		$LastDir = $prefs->load('Explorer.LastDir', 1);
		if (is_null($LastDir)) {
			$path = $fs->GetTopDir();
		}
		else {
			$path = $LastDir;
		}
	}
	$arrFile = $fs->readDirWeb($path);
    $prefs->save('Explorer.LastDir', $path, 1);
	$fs->render($arrFile, $_GET['Type'], isset($_GET['Filter']) ? $_GET['Filter'] : false);
}