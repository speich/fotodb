<?php

use PhotoDatabase\Search\FtsFunctions;
use PhotoDatabase\Search\SearchQuery;
use Vanderlee\Syllable\Syllable;

require_once __DIR__.'/library/vendor/autoload.php';


$text = "Autobahnraststätte Île-de-France! #Füchse# beim Spielen Waldreservat Rotfuchs. Aus Gründen des Naturschutzes/Geheimhaltung werden keine Koordinaten angezeigt.
    Menschen stehen vor dem Aquarium und betrachten, filmen oder fotografieren die Walhaie im Tank? Graureiher Tafelente";
$text = FtsFunctions::removePunctuation($text);
$words = SearchQuery::extractWords($text, 100);

function inDictionary($dict, $word) {
    if (enchant_dict_check($dict, $word)) {
        return $word;
    }

    if (enchant_dict_check($dict, ucfirst($word))) {
        return ucfirst($word);
    }

    return false;
}

$tag = 'de_CH';
$broker = enchant_broker_init();
$dict = enchant_broker_request_dict($broker, $tag);

$syllable = new Syllable('de-ch-1901');
$syllable->setMinWordLength(4);

foreach ($words as $word) {
    // 1. insert word as is
    // 2. iteratively remove first syllable and then insert word until min length is reached
    $syllables = $syllable->splitWord($word);
    echo "<p><strong>$word</strong><br>";
    if (count($syllables) > 1) {
        foreach ($syllables as $token) {
            array_shift($syllables);
            $part = implode("", $syllables);
            $part = inDictionary($dict, $part);
            if ($part !== false && mb_strlen($part, 'utf-8') > 3) {
                echo $part."<br>";
            }
        }
    }
    echo '</p>';
}

enchant_broker_free_dict($dict);
enchant_broker_free($broker);



