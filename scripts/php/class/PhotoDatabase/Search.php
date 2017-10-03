<?php

use PhotoDatabase\Database;

/**
 * Created by JetBrains PhpStorm.
 * User: Simon
 * Date: 21.01.11
 * Time: 18:47
 * To change this template use File | Settings | File Templates.
 */
 
class Search extends Database {
	/** @var array words that are not inserted into search index. */
	var $stopWords = array(
		'ab', 'aber', 'aber', 'ach', 'acht', 'achte', 'achten', 'achter', 'achtes', 'ag', 'alle', 'allein', 'allem', 'allen', 'aller', 'allerdings', 'alles', 'allgemeinen', 'als', 'als', 'also', 'am', 'an', 'andere', 'anderen', 'andern', 'anders', 'au', 'auch', 'auch', 'auf', 'aus', 'ausser', 'außer', 'ausserdem', 'außerdem',
		'bald', 'bei', 'beide', 'beiden', 'beim', 'bekannt', 'bereits', 'besonders', 'besser', 'besten', 'bin', 'bis', 'bisher', 'bist',
		'da', 'dabei', 'dadurch', 'dafür', 'dagegen', 'daher', 'dahin', 'dahinter', 'damals', 'damit', 'danach', 'daneben', 'dank', 'dann', 'daran', 'darauf', 'daraus', 'darf', 'darfst', 'darin', 'darüber', 'darum', 'darunter', 'das', 'das', 'dasein', 'daselbst', 'dass', 'daß', 'dasselbe', 'davon', 'davor', 'dazu', 'dazwischen', 'dein', 'deine', 'deinem', 'deiner', 'dem', 'dementsprechend', 'demgegenüber', 'demgemäss', 'demgemäß', 'demselben', 'demzufolge', 'den', 'denen', 'denn', 'denn', 'denselben', 'der', 'deren', 'derjenige', 'derjenigen', 'dermassen', 'dermaßen', 'derselbe', 'derselben', 'des', 'deshalb', 'desselben', 'dessen', 'deswegen', 'd.h', 'dich', 'die', 'diejenige', 'diejenigen', 'dies', 'diese', 'dieselbe', 'dieselben', 'diesem', 'diesen', 'dieser', 'dieses', 'dir', 'doch', 'dort', 'drei', 'drin', 'dritte', 'dritten', 'dritter', 'drittes', 'du', 'durch', 'durchaus',
		'eben', 'ebenso', 'eigen', 'eigene', 'eigenen', 'eigener', 'eigenes', 'ein', 'einander', 'eine', 'einem', 'einen', 'einer', 'eines', 'einige', 'einigen', 'einiger', 'einiges', 'einmal', 'einmal', 'eins', 'elf', 'en', 'ende', 'endlich', 'entweder', 'entweder', 'er', 'ernst', 'erst', 'erste', 'ersten', 'erster', 'erstes', 'es', 'etwa', 'etwas', 'euch',
		'früher', 'fünf', 'fünfte', 'fünften', 'fünfter', 'fünftes', 'für',
		'gab', 'ganz', 'ganze', 'ganzen', 'ganzer', 'ganzes', 'gar', 'gedurft', 'gegen', 'gegenüber', 'gehabt', 'gehen', 'geht', 'gekannt', 'gekonnt', 'gemacht', 'gemocht', 'gemusst', 'genug', 'gerade', 'gern', 'gesagt', 'gesagt', 'geschweige', 'gewesen', 'gewollt', 'geworden', 'gibt', 'ging', 'gleich', 'gott', 'gross', 'groß', 'grosse', 'große', 'grossen', 'großen', 'grosser', 'großer', 'grosses', 'großes', 'gut', 'gute', 'guter', 'gutes',
		'habe', 'haben', 'habt', 'hast', 'hat', 'hatte', 'hätte', 'hatten', 'hätten', 'heisst', 'her', 'heute', 'hier', 'hin', 'hinter', 'hoch',
		'ich', 'ihm', 'ihn', 'ihnen', 'ihr', 'ihre', 'ihrem', 'ihren', 'ihrer', 'ihres', 'im', 'im', 'immer', 'in', 'in', 'indem', 'infolgedessen', 'ins', 'irgend', 'ist',
		'ja', 'ja', 'jahr', 'jahre', 'jahren', 'je', 'jede', 'jedem', 'jeden', 'jeder', 'jedermann', 'jedermanns', 'jedoch', 'jemand', 'jemandem', 'jemanden', 'jene', 'jenem', 'jenen', 'jener', 'jenes', 'jetzt',
		'kam', 'kann', 'kannst', 'kaum', 'kein', 'keine', 'keinem', 'keinen', 'keiner', 'kleine', 'kleinen', 'kleiner', 'kleines', 'kommen', 'kommt', 'können', 'könnt', 'konnte', 'könnte', 'konnten', 'kurz',
		'lang', 'lange', 'lange', 'leicht', 'leide', 'lieber', 'los',
		'machen', 'macht', 'machte', 'mag', 'magst', 'mahn', 'man', 'manche', 'manchem', 'manchen', 'mancher', 'manches', 'mann', 'mehr', 'mein', 'meine', 'meinem', 'meinen', 'meiner', 'meines', 'mich', 'mir', 'mit', 'mittel', 'mochte', 'möchte', 'mochten', 'mögen', 'möglich', 'mögt', 'morgen', 'muss', 'muß', 'müssen', 'musst', 'müsst', 'musste', 'mussten',
		'na', 'nach', 'nachdem', 'nahm', 'natürlich', 'neben', 'nein', 'neue', 'neuen', 'neun', 'neunte', 'neunten', 'neunter', 'neuntes', 'nicht', 'nicht', 'nichts', 'nie', 'niemand', 'niemandem', 'niemanden', 'noch', 'nun', 'nun', 'nur',
		'ob', 'oben', 'oder', 'oder', 'offen', 'oft', 'oft', 'ohne',
		'recht', 'rechte', 'rechten', 'rechter', 'rechtes', 'richtig', 'rund',
		'sa', 'sache', 'sagt', 'sagte', 'sah', 'satt', 'schon', 'sechs', 'sechste', 'sechsten', 'sechster', 'sechstes', 'sehr', 'sei', 'sei', 'seid', 'seien', 'sein', 'seine', 'seinem', 'seinen', 'seiner', 'seines', 'seit', 'seitdem', 'selbst', 'selbst', 'sich', 'sie', 'sieben', 'siebente', 'siebenten', 'siebenter', 'siebentes', 'sind', 'so', 'solang', 'solche', 'solchem', 'solchen', 'solcher', 'solches', 'soll', 'sollen', 'sollte', 'sollten', 'sondern', 'sonst', 'sowie', 'später', 'statt',
		'tat', 'teil', 'tel', 'tritt', 'trotzdem', 'tun',
		'über', 'überhaupt', 'übrigens', 'uhr', 'um', 'und', 'und?', 'uns', 'unser', 'unsere', 'unserer', 'unter',
		'vergangenen', 'viel', 'viele', 'vielem', 'vielen', 'vielleicht', 'vier', 'vierte', 'vierten', 'vierter', 'viertes', 'vom', 'von', 'vor',
		'wahr?', 'während', 'währenddem', 'währenddessen', 'wann', 'war', 'wäre', 'waren', 'wart', 'warum', 'was', 'wegen', 'weil', 'weit', 'weiter', 'weitere', 'weiteren', 'weiteres', 'welche', 'welchem', 'welchen', 'welcher', 'welches', 'wem', 'wen', 'wenig', 'wenig', 'wenige', 'weniger', 'weniges', 'wenigstens', 'wenn', 'wenn', 'wer', 'werde', 'werden', 'werdet', 'wessen', 'wie', 'wie', 'wieder', 'will', 'willst', 'wir', 'wird', 'wirklich', 'wirst', 'wo', 'wohl', 'wollen', 'wollt', 'wollte', 'wollten', 'worden', 'wurde', 'würde', 'wurden', 'würden',
		'z.b', 'zehn', 'zehnte', 'zehnten', 'zehnter', 'zehntes', 'zeit', 'zu', 'zuerst', 'zugleich', 'zum', 'zum', 'zunächst', 'zur', 'zurück', 'zusammen', 'zwanzig', 'zwar', 'zwei', 'zweite', 'zweiten', 'zweiter', 'zweites', 'zwischen', 'zwölf'
	);

	public function __construct() {
		parent::__construct('Private');

		mb_internal_encoding('UTF-8');
		mb_regex_encoding('UTF-8');

		// Check if structure for searching was already created otherwise create it
		$this->Db = $this->Connect();
		$sql = "SELECT tbl_name FROM sqlite_master
			WHERE tbl_name IN ('searchIndex', 'searchOccurrences') AND type = 'table'";
		$stmt = $this->Db->prepare($sql);
  		$stmt->execute();
		$arr = $stmt->fetchAll(PDO::FETCH_NUM);
		if (count($arr) == 0) {
			$this->createStructure();
		}
		$this->Db = null;
	}

	/**
	 * Updated search index.
	 * Inserts new words into search index. Only words are inserted from records that have changed since
	 * the last update unless you set $full to true
	 * @param bool $full
	 * @return void
	 */
	public function updateIndex($full = false) {

		$db = $this->Db;
		// Note: Unfortunately SQLite fulltext search is only available as of PHP 5.3

		// For speed reason we turn journaling off and increase cache size.
		// As long as we import all records at once, we do not need a rollback.
		// We just start over in case of a crash.
		// default = 2000 pages, 1 page = 1kb;
		$sql = "PRAGMA journal_mode = OFF; PRAGMA cache_size = 10000;";
		$this->Db->exec($sql);

  		// Loop through table images and add all words from its records
		// concat activities, otherwise we might have more than one record per images record
		$sql = "SELECT I.id imgId, I.imgTechInfo imgTechInfo,	I.imgDesc imgDesc, I.imgTitle imgTitle,
			GROUP_CONCAT(DISTINCT T.NameDe) themes,
			GROUP_CONCAT(DISTINCT S.NameDe) subjectAreas,
			GROUP_CONCAT(DISTINCT K.Name) keywords,
			N.nameDe wissNameDe, N.NameLa wissNameLa,
			GROUP_CONCAT(DISTINCT L.Name) locations, GROUP_CONCAT(DISTINCT C.NameDe) countries
			FROM Images I
			LEFT JOIN Images_Themes IT ON I.id = IT.imgId
			LEFT JOIN Themes T ON IT.themeId = T.id
			LEFT JOIN SubjectAreas S ON T.SubjectAreaId = S.Id
			LEFT JOIN Images_Keywords IK ON I.id = IK.imgId
			LEFT JOIN Keywords K ON IK.keywordId = K.id
			LEFT JOIN Images_ScientificNames ISc ON I.id = ISc.imgId
			LEFT JOIN ScientificNames N ON ISc.scientificNameId = N.id
			LEFT JOIN Images_Locations IL ON I.id = IL.imgId
			LEFT JOIN Locations L ON IL.locationId = L.id
			LEFT JOIN Locations_Countries LC ON L.id = LC.locationId
			LEFT JOIN Countries C ON LC.countryId = C.id";
		if ($full === false) {
			$sql.= " WHERE (LastChange > DatePublished OR DatePublished IS NULL)";
		}
		$sql.= "	GROUP BY imgId";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$arrData = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$db->beginTransaction();
	   if ($full === true) {
			// Delete all previously inserted records
			$db->exec('DELETE FROM searchIndex');
			$db->exec('DELETE FROM searchOccurrences');
		   echo 'previous search index deleted<br>';
	   }

		// since we set the constraint to UNIQUE ON CONFLICT IGNORE
		// we can insert without checking if word was already inserted
		$sql = "INSERT INTO searchIndex (id, word) VALUES (NULL, :word)";
		$stmt1 = $db->prepare($sql);
		$stmt1->bindParam('word', $word);

		$sql = "SELECT id FROM searchIndex WHERE word = :word";
		$stmt2 = $db->prepare($sql);
		$stmt2->bindParam('word', $word);

		$sql = "INSERT INTO searchOccurrences (wordId, recordId) VALUES (:wordId, :imgId)";
		$stmt3 = $db->prepare($sql);
		$stmt3->bindParam('wordId', $wordId);
		$stmt3->bindParam('imgId', $imgId);

		$arrCol = array('imgTechInfo', 'imgDesc', 'imgTitle', 'themes', 'subjectAreas', 'keywords', 'wissNameDe', 'wissNameLa', 'locations', 'countries');
		foreach ($arrData as $row) {
			foreach ($arrCol as $col) {
				$column = $row[$col];
				if (!empty($column)) {
					$words = $this->getWords($column);
					foreach ($words as $word) {
						$stmt1->execute();
						$stmt2->execute();
						$row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
						$wordId = $row2['id'];
						$imgId = $row['imgId'];
						$stmt3->execute();
						echo "$word ";
					}
					echo "<br>";
					flush();
				}
			}
		}
		$db->commit();
		echo 'done inserting search words';
	}

	/**
	 * Split text into words.
	 * Splits text into words without the noise.
	 * @param string $text
	 * @return array
	 */
	public function splitText($text) {
  		return preg_split('/([^a-zA-ZÄÖÜßäëïöüáéíóúè]+)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * Checks if a word is a stop word.
	 * Stop words are not to be included in the search index.
	 * @param string $word
	 * @return bool
	 */
	public function checkStopWord($word) {
  		return in_array($word, $this->stopWords);
	}

	/**
	 * Extracts the words from a string.
	 * Prepares the words for inserting in the index.
	 * @param string $text
	 * @return array
	 */
	public function getWords($text) {
		$arr = array();
		$words = $this->splitText($text);
		foreach ($words as $word) {
			if (strlen($word) < 3) {
				continue;
			}
			$word = mb_strtolower($word);
			if ($this->checkStopWord($word)) {
				continue;
			}
			$arr[] = $word;
		}
		return $arr;
	}

	/**
	 * Create the database tables necessary for searching.
	 */
	private function createStructure() {
		$sql = "BEGIN;
		  	CREATE TABLE searchIndex (id INTEGER PRIMARY KEY, word TEXT UNIQUE ON CONFLICT IGNORE);
			CREATE TABLE searchOccurrences (
				wordId INTEGER NOT NULL,
				recordId INTEGER NOT NULL
			);
			COMMIT;";
		$this->Db->exec($sql);
		echo 'done creating search structure.<br/>';
		print_r($this->Db->errorInfo());
	}

}
