<?php /** @noinspection ForgottenDebugOutputInspection */

use PhotoDatabase\Search\FtsFunctions;
use PhotoDatabase\Search\IndexingTools;
use PhotoDatabase\Search\SearchQuery;
use PhotoDatabase\Search\SqlKeywordsSource;


require_once __DIR__.'/library/vendor/autoload.php';
require_once __DIR__.'/scripts/php/inc_script.php';

$text = 'Autobahnraststätte Île-de-France! #Füchse# beim Spielen Waldreservat Rotfuchs. Aus Gründen des Naturschutzes/Geheimhaltung werden keine Koordinaten angezeigt.
    Menschen stehen vor dem Aquarium und betrachten, filmen oder fotografieren die Walhaie im Tank? Graureiher Tafelente! Wasseramsel';
$sql = new SqlKeywordsSource();

var_dump(SearchQuery::extractWords($text));
$indexer = new IndexingTools(6);
$prefixes = $indexer->createPrefixesFromSyllables($text, 3, true);
var_dump($prefixes);

$prefixes = $indexer->createPrefixesFromAll($text, 6, true);
var_dump($prefixes);

$indexer->cleanup();


// create the example database
try {
    $db = new PDO('sqlite:example.sqlite');
    $db->sqliteCreateFunction('SCORE', [FtsFunctions::class, 'tfIdf']);
    $db->sqliteCreateFunction('SCOREWEIGHTED', [FtsFunctions::class, 'tfIdfWeighted']);
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
$data = $db->query("SELECT imgId, MATCHINFO(images) info, 
       SCORE(MATCHINFO(images, 'xncp')) rank,
       SCOREWEIGHTED(MATCHINFO(images, 'xncp'), '0,2,1,1.5,1') rankWeighted FROM images WHERE images MATCH 'green woodpecker'");

// convert the binary output to integers and format integers in groups of three
while ($row = $data->fetch(PDO::FETCH_ASSOC)) {

    $arrInt32 = unpack('L*', $row['info']);
    echo "<p>";
    echo formatOutput($arrInt32)."<br>";
    echo $row['rank']."<br>";
    echo $row['rankWeighted']."<br>";
    echo "</p>";
}

function formatOutput($arrInt32)
{
    $str = '';
    foreach ($arrInt32 as $i => $int) {
        $str .= ($i % 3 === 0 ? ' ' : '').$int;
    }

    return $str;
}