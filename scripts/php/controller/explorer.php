<?php
use PhotoDatabase\Database;
use PhotoDatabase\Preferences;

include __DIR__.'/../../classes/class_website.php';
include __DIR__ . '/../../classes/PhotoDatabase/Database.php';
include __DIR__ . '/../../classes/FileExplorer.php';
require_once __DIR__.'/../../classes/PhotoDatabase/Preferences.php';
error_reporting(E_ERROR);

// note: all paths should end with a slash
$web = new Website();
$prefs = new Preferences();
$db = new Database();
$db->connect();
$fs = new FileExplorer($db);
$fs->SetTopDir($web->GetWebRoot().'dbprivate/images/');

if (isset($_GET['Type'])) {
	if (isset($_GET['Dir'])) {
		$Path = preg_replace("/[\"'<>;:]/", "", $_GET['Dir']);
	}
	else {
		$LastDir = $prefs->load('Explorer.LastDir', 1);
		if (is_null($LastDir)) {
			$Path = $fs->GetTopDir();
		}
		else {
			$Path = $LastDir;
		}
	}
	$arrFile = $fs->ReadDirWeb($Path);
    $prefs->save('Explorer.LastDir', $Path, 1);
	$fs->render($arrFile, $_GET['Type'], isset($_GET['Filter']) ? $_GET['Filter'] : false);
}