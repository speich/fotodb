<?php

use PhotoDatabase\Search\KeywordsIndexer;
use PhotoDatabase\Search\SqlKeywordsSource;


require_once __DIR__.'/library/vendor/autoload.php';
require_once __DIR__.'/scripts/php/inc_script.php';

$text = 'Autobahnraststätte Île-de-France! #Füchse# beim Spielen Waldreservat Rotfuchs. Aus Gründen des Naturschutzes/Geheimhaltung werden keine Koordinaten angezeigt.
    Menschen stehen vor dem Aquarium und betrachten, filmen oder fotografieren die Walhaie im Tank? Graureiher Tafelente!';
$sql = new SqlKeywordsSource();
var_dump(\PhotoDatabase\Search\SearchQuery::extractWords($text));
$indexer = new KeywordsIndexer($db->db, $sql);
$prefixes = $indexer->createPrefixes($text, false);
echo "<br>$word:";
var_dump($prefixes);
$indexer->cleanup();


// create the example database
try {
    $db = new PDO('sqlite:example.sqlite');
} catch (PDOException $error) {
    echo $error->getMessage();
}

// create a virtual fts4 table and populate it with example data
try {
    $db->exec("CREATE VIRTUAL TABLE images USING fts4(imgId, title, description, species, speciesEn);
        INSERT INTO images VALUES(1, 'Great Spotted Woodpecker', 'A great spotted woodpecker with a caterpillar in its beak.', 'Dendrocopos major', 'Great Spotted woodpecker');
        INSERT INTO images VALUES(2, 'Woodpecker at the pond', 'A green woodpecker drinks water.', 'Picus viridis', 'Green Woodpecker');
        INSERT INTO images VALUES(3, 'Woodpecker', 'A middle spotted woodpecker is looking for food on an oak tree.', 'Dendrocopos medius', 'Middle Spotted Woodpecker');
        INSERT INTO images VALUES(4, 'Woodpecker', 'A lesser yellownape showing its green wings.', 'Picus chlorolophus', 'Lesser Yellownape');");
} catch (PDOException $error) {
    echo $error->getMessage().'<br>';
}

// use matchinfo when searching for green woodpecker using an implicit AND operator
$data = $db->query("SELECT imgId, MATCHINFO(images, 'xncp') info FROM images WHERE images MATCH 'green woodpecker'");

// convert the binary output to integers and format integers in groups of three
while ($row = $data->fetch(PDO::FETCH_ASSOC)) {
    // matchinfo returns a blob of 32-bit unsigned integers in machine byte-order
    // note: returned array starts at index 1, not 0
    $arrInt32 = unpack('L*', $row['info']);
    $numPhrases = array_pop($arrInt32);
    $numCols = array_pop($arrInt32);
    $numRows = array_pop($arrInt32);
echo implode("", $arrInt32)."<br>";
    $score = 0;
    foreach ($arrInt32 as $i => $int) {
        $remainder = ($i - 1) % 3;
        if ($remainder === 0) {
            $tf = $int;   // term frequency
        } elseif ($remainder === 2) {
            $df = $int;   // document frequency
            $idf = $df > 0 ? log10($numCols * $numRows / $df) : 0;
            $score += $tf * $idf;
        }
    }
    echo "score: $score<br>";
}