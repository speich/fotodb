<?php 
include 'inc_script.php';

echo "// this array contains all bird names and is used as a lookup for filtering the bird name fields\n";
echo "// it's in this external js file for caching reason (created by php)\n";
echo "var arrBird = [";
$rst = $db->Db->query("SELECT * FROM BirdNames", SQLITE_ASSOC);
$numBirds = $rst->numRows();
$i = 0;
foreach ($rst as $row) {
	echo "[".$row['Id'].",'".addslashes($row['NameDe'])."','".addslashes($row['NameEn'])."','".addslashes($row['NameLa'])."']";
	if ($i < $numBirds-1) { echo ","; }
	$i++;
}
echo "];\n";