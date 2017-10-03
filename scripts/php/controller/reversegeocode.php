<?php

use PhotoDatabase\Database\Database;

require_once '../../../dbprivate/inc_script.php';

$Db = new Database();

if (isset($_GET['Lng']) && isset($_GET['Lat'])) {
    // e.g. http://fotodb/dbprivate/library/reversegeocode.php?Lat=47.989921667414166&Lng=9.140625
    $lat = round($_GET['Lat'], 6);
    $lng = round($_GET['Lng'], 6);

    // Get nearby place name, reverse geocode and merge the two xml into one.
    $xmlGeoNames = getLocation($lat, $lng, 0.5);

    $xmlFotoDb = new DOMDocument('1.0', 'UTF-8');
    $xmlFotoDb->preserveWhiteSpace = false;
    $root = $xmlFotoDb->createElement('FotoDbGeoData');
    $root->setAttribute('xml:lang', 'de-CH');
    $root = $xmlFotoDb->appendChild($root);
    $Country = $xmlGeoNames->getElementsByTagName('countryName');   // when clicking on ocean, country can be empty
    $el = $xmlFotoDb->createElement('CountryName');
    $Text = $Country->length > 0 && $Country->item(0) && $Country->item(0)->firstChild ? $Country->item(0)->firstChild->nodeValue : '';
    $el->appendChild($xmlFotoDb->createTextNode($Text));
    $root->appendChild($el);

    $NodeList = $xmlGeoNames->getElementsByTagName('name');
    foreach ($NodeList as $Node) {
        if ($Node->nodeType == XML_ELEMENT_NODE) {
            $el = $xmlFotoDb->createElement('GeoName');
            $el->setAttribute('Level', '2');
            $Text = $Node->firstChild->nodeValue;
            $el->appendChild($xmlFotoDb->createTextNode($Text));
            $root->appendChild($el);
        }
    }
    /*$NodeList = $Xml2->getElementsByTagName('adminName1');
    foreach ($NodeList as $Node) {
        if ($Node->nodeType == XML_ELEMENT_NODE) {
            $El = $Xml3->createElement('GeoName');
            $El->setAttribute('Level', '1');
            $Text = $Node->firstChild->nodeValue;
            $El->appendChild($Xml3->createTextNode($Text));
            $Root->appendChild($El);
        }
    }*/
    header('Content-type: application/xml; charset="utf-8"');
    echo $xmlFotoDb->saveXML();
}

/**
 *
 * @param $lat
 * @param $lng
 * @param $r
 * @return DOMDocument|null
 */
function getLocation($lat, $lng, $r)
{
    $usr = 'speichnet';
    $query = '?lat=' . $lat . '&lng=' . $lng . '&radius=' . $r . '&username=' . $usr;
    $strFile = file_get_contents('http://api.geonames.org/findNearbyPlaceName' . $query);

    if (!$strFile) {
        return null;
    }
    $xml = new DOMDocument();
    $xml->loadXML($strFile);
    if (!$xml) {
        return null;
    }

    $geonames = $xml->getElementsByTagName('geonames')->item(0);
    if (!$geonames->hasChildNodes()) {
        $r *= 2;
        $xml = getLocation($lat, $lng, $r);
    }
    $geoname = $xml->getElementsByTagName('geoname');
    if ($geoname->length === 0 && $r < 33) {
        $r *= 2;
        $xml = getLocation($lat, $lng, $r);
    }

    return $xml;
}