<?php

use LFI\Results\ResultSearch;
use PhotoDatabase\Database\Database;
use PhotoDatabase\Database\SearchKeywords;
use WebsiteTemplate\Header;


require_once '../inc_script.php';

$db = new Database($config);
$db = $db->connect();




$header = new Header();
$ranges = $header->getRange();
$search = new SearchKeywords($db);
$labels = SearchKeywords::extractWords($_GET['Q'], 1, 0);
$word = str_replace(['*','"'], '', $labels[0]);
//$numWords = $service->getNumLabels($word, $prodNrs['PRODNR']);
//$service->limit = $ranges['end'] - $ranges['start'];
//$service->offset = $ranges['start'];
$result = $search->search('wäld');
echo $result;



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