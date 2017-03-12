<?php
include __DIR__.'/../../classes/class_website.php';
include __DIR__.'/../../classes/class_fotodb.php';
include __DIR__.'/../../classes/class_explorer.php';
error_reporting(E_ERROR);

// note: all paths should end with a slash

$Fs = new Explorer();
$Fs->Connect();
$Fs->SetTopDir($Fs->GetWebRoot().'dbprivate/images/');

if (isset($_GET['Type'])) {
	if (isset($_GET['Dir'])) {
		$Path = preg_replace("/[\"'<>;:]/", "", $_GET['Dir']);
	}
	else {
		$LastDir = $Fs->LoadPref('Explorer.LastDir', 1);
		if (is_null($LastDir)) {
			$Path = $Fs->GetTopDir();
		}
		else {
			$Path = $LastDir;
		}
	}
	$arrFile = $Fs->ReadDirWeb($Path);
	$Fs->SavePref('Explorer.LastDir', $Path, 1);
	$Fs->Render($arrFile, $_GET['Type'], isset($_GET['Filter']) ? $_GET['Filter'] : false);
}