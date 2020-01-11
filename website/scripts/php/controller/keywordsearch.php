<?php

use PhotoDatabase\Database\Database;
use PhotoDatabase\Search\Keywords;
use WebsiteTemplate\Header;


require_once __DIR__.'/../inc_script.php';

$db = new Database($config);
$db = $db->connect();




$header = new Header();
$ranges = $header->getRange();
$search = new Keywords($db);
$query = $search->prepareQuery($_GET['q']);
$result = $search->search($query);
var_dump($result);



// re-format array to match expected output for dijit/combobox

/*if ($words) {   // can be false or 0 records
    $arr2 = [];
    foreach ($words as $label) {
        $arr2[] = ['Q' => $label];
    }
    $response = json_encode($arr2);
} else {
    $response = json_encode([]);
}



$rangeHeader = $header->createRange($ranges, $numWords);
header($rangeHeader[0].': '.$rangeHeader[1]);
header('Content-Type: '.$header->getContentType().'; '.$header->getCharset());
echo $response;*/