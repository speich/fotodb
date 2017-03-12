<?php
include '../../classes/class_website.php';
include '../../classes/class_fotodb.php';

$Db = new FotoDb('Private');

if (isset($_GET['Lng']) && isset($_GET['Lat'])) {
	// e.g. http://fotodb/dbprivate/library/reversegeocode.php?Lat=47.989921667414166&Lng=9.140625
	$Lat = round($_GET['Lat'], 6);
	$Lng = round($_GET['Lng'], 6);

	// Get nearby place name, reverse geocode and merge the two xml into one.
	$Xml1 = getLocation($Lat, $Lng, 1);
	$Xml2 = getCountry($Lat, $Lng);

	$Xml3 = new DOMDocument('1.0', 'UTF-8');
	$Xml3->preserveWhiteSpace = false;
	$Root = $Xml3->createElement('FotoDbGeoData');
	$Root->setAttribute('xml:lang', 'de-CH');
	$Root = $Xml3->appendChild($Root);
	
	$Country = $Xml2->getElementsByTagName('countryName');	// when clicking on ocean, country can be empty
	$El = $Xml3->createElement('CountryName');
	$Text = $Country->length > 0 && $Country->item(0) && $Country->item(0)->firstChild ? $Country->item(0)->firstChild->nodeValue : '';
	$El->appendChild($Xml3->createTextNode($Text));
	$Root->appendChild($El);
	
	$NodeList = $Xml1->getElementsByTagName('name');
	foreach ($NodeList as $Node) {
		if ($Node->nodeType == XML_ELEMENT_NODE) {
			$El = $Xml3->createElement('GeoName');
			$El->setAttribute('Level', '2');
			$Text = $Node->firstChild->nodeValue;
			$El->appendChild($Xml3->createTextNode($Text));
			$Root->appendChild($El);
		}
	}
	$NodeList = $Xml2->getElementsByTagName('adminName1');
	foreach ($NodeList as $Node) {
		if ($Node->nodeType == XML_ELEMENT_NODE) {
			$El = $Xml3->createElement('GeoName');
			$El->setAttribute('Level', '1');
			$Text = $Node->firstChild->nodeValue;
			$El->appendChild($Xml3->createTextNode($Text));
			$Root->appendChild($El);
		}
	}
	header('Content-type: application/xml; charset="utf-8"');
	echo $Xml3->saveXML();
}

/**
 * @param $lat
 * @param $lng
 * @return DOMDocument|null
 */
function getCountry($lat, $lng) {
	$usr = 'speichnet';
	$query = '?lat='.$lat.'&lng='.$lng.'&username='.$usr;
	$strFile = file_get_contents('http://api.geonames.org/countrySubdivision'.$query);
	if (!$strFile) { return null; }
	$xml = new DOMDocument();
	$xml->loadXML($strFile);
	if (!$xml) { return null; }
	return $xml;
}

/**
 * @param $lat
 * @param $lng
 * @param $r
 * @return DOMDocument|null
 */
function getLocation($lat, $lng, $r) {
	$usr = 'speichnet';
	$query = '?lat='.$lat.'&lng='.$lng.'&radius='.$r.'&username='.$usr;
	$strFile = file_get_contents('http://api.geonames.org/findNearbyPlaceName'.$query);

	if (!$strFile) { return null; }
	$xml = new DOMDocument();
	$xml->loadXML($strFile);
	if (!$xml) { return null; }

	$geoname = $xml->getElementsByTagName('geoname');
	if ($geoname->length === 0 && $r < 33) {
		$r *= 2;
		$xml = getLocation($lat, $lng, $r);
	}

	return $xml;
}