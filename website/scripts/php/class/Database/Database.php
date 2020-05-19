<?php

namespace PhotoDatabase\Database;

use DOMDocument;
use DOMElement;
use PDO;
use SQLite3;
use PhotoDatabase\ExifService;
use stdClass;


/**
 * Class to work with SQLite databases.
 * Creates the photo DB.
 *
 */
class Database
{
    /** @var PDO $db db instance of SQLite */
    public $db;
    // paths are always appended to webroot ('/' or a subfolder) and start therefore with a foldername
    // and not with a slash, but end with a slash

    private $folderImageOriginal;    // absolute path where image originals are stored*/
    protected $hasActiveTransaction = false;    // keep track of open transactions
    private $webroot = '/';
    private $exiftool;
    private $dbPath;
    private $pathImg;    // relative to this class

    /**
     * @constructor
     * @param stdClass $config
     */
    public function __construct($config)
    {
        $this->pathImg = $config->paths->imagesWebRoot;
        $this->folderImageOriginal = $config->paths->imagesOriginal;
        $this->exiftool = $config->paths->exifTool;
        $this->dbPath = $config->paths->database;
    }

    /**
     * Connect to the SQLite photo database.
     *
     * If you set the argument $UseNativeDriver to true the native SQLite driver
     * is used instead of PDO.
     * @return PDO
     */
    public function connect()
    {
        if ($this->db === null) {   // check if not already connected
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
            $this->db = new PDO('sqlite:'.$this->dbPath, null, null, $options);
            $isCreated = file_exists($this->dbPath);
            if (!$isCreated) {
                $this->createStructure();
            }
            // Do every time you connect since they are only valid during connection (not permanent)
            $this->db->sqliteCreateAggregate(
                'GROUP_CONCAT',
                [$this, 'groupConcatStep'],
                [$this, 'groupConcatFinalize']
            );
            $this->db->sqliteCreateFunction('STRTOTIME', [$this, 'strToTime']);
//				$this->Db->sqliteCreateFunction('LOCALE', array($this, 'GetSortOrder'), 1);
            $this->db->exec('pragma short_column_names = 1');
        }

        return $this->db;
    }

    /**
     * Returns a properly escaped string that may be used safely in an SQL statement.
     * @param string $string string to be escaped
     * @return string escaped string
     */
    function escapeString($string)
    {
        // sqlite_escape_string is not supported in php 5.4 anymore
        return SQLite3::escapeString($string);
    }

    /**
     * Open transaction with a flag that you can check if it is already started.
     * PDO whould throw an error if you opend a transaction which is already open
     * and does not provide a means of checking status. So use this method instead
     * together with Commit and RollBack.
     * @return bool
     */
    public function beginTransaction()
    {
        if ($this->hasActiveTransaction === true) {
            return false;
        }
        $this->hasActiveTransaction = $this->db->beginTransaction();

        return $this->hasActiveTransaction;
    }

    /**
     * Comit transaction and set flag to false.
     * @return bool
     */
    public function commit()
    {
        $this->hasActiveTransaction = false;

        return $this->db->commit();
    }

    /**
     * Rollback transaction and set flag to false.
     * @return bool
     */
    function rollback()
    {
        $this->hasActiveTransaction = false;

        return $this->db->rollback();
    }

    /**
     * Returns the file name of the database.
     * @return string
     */
    public function getDbName(): string
    {
        $parts = pathinfo($this->dbPath);

        return $parts['filename'];
    }

    /**
     * Provides access to the different paths in the FotoDB project.
     * @param string $Name
     * @return string
     */
    public function getPath($Name)
    {
        $path = '';
        switch ($Name) {
            case 'WebRoot':
                $path = $this->getWebRoot();
                break;   // redundant, but for convenience
            case 'Db':
                $path = $this->dbPath;
                break;
            case 'Img':
                $path = $this->pathImg;
                break;
            case 'ImgOriginal':
                $path = $this->folderImageOriginal;
        }

        return $path;   // pdo functions need full path to work with subfolders on windows
    }

    /**
     * Insert new image data from form and from exif data.
     *
     * This method is only called once, when the image is selected by the user for the first time.
     * @param string $imgSrc image file including web root path
     * @return string XML file
     */
    public function insert($img)
    {
        $imgFolder = str_replace($this->getWebRoot().ltrim($this->getPath('Img'), '/'), '', $img);   // remove web images folder path part
        $imgName = substr($imgFolder, strrpos($imgFolder, '/') + 1);
        $imgFolder = trim(str_replace($imgName, '', $imgFolder), '/');
        $sql = 'INSERT INTO Images (Id, ImgFolder, ImgName, DateAdded, LastChange)
			VALUES (NULL, :imgFolder, :imgName,'.time().','.time().')';
        $this->beginTransaction();
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgName', $imgName);
        $stmt->bindParam(':imgFolder', $imgFolder);
        $stmt->execute();
        $imgId = $this->db->lastInsertId();
        $imgSrc = $imgFolder.'/'.$imgName;
        // insert exif data
        $exifData = $this->getExif($imgSrc);
        if (!$this->insertExif($imgId, $exifData)) {
            echo 'failed';

            return false;
        }
        if (array_key_exists('XMP', $exifData) && !$this->insertXmp($imgId, $exifData['XMP'])) {
            echo 'failed';

            return false;
        }
        $sql = 'SELECT Id, ImgFolder, ImgName, ImgDateManual, ImgTechInfo, FilmTypeId, RatingId,
			DateAdded, LastChange, ImgDesc,	ImgTitle, Public, DatePublished, ImgDateOriginal, ImgLat, ImgLng, ShowLoc, CountryId
			FROM Images WHERE Id = :ImgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':ImgId', $imgId);
        $stmt->execute();
        $this->commit();
        $strXml = '<?xml version="1.0" encoding="UTF-8"?>';
        $strXml .= '<HtmlFormData xml:lang="de-CH">';
        // image data
        $strXml .= '<Image';
        foreach ($stmt->fetch() as $key => $val) {
            // each col in db is attribute of xml element Image
            if (strpos($key, 'Date') !== false && $key !== 'ImgDateManual' && $val !== null && $val !== '') {
                $strXml .= ' '.$key.'="'.date('d.m.Y H:i:s', $val).'"';
            } elseif ($key === 'LastChange' && $val !== null && $val !== '') {
                $strXml .= ' '.$key.'="'.date('d.m.Y H:i:s', $val).'"';
            } else {
                $strXml .= ' '.$key.'="'.$val.'"';
            }
        }
        $strXml .= '/>';
        $strXml .= '</HtmlFormData>';
        header('Content-Type: text/xml; charset=UTF-8');
        echo $strXml;

        return true;
    }

    /**
     * Edit image data.
     *
     * Data is selected from database and posted back as an xml page.
     * Response is returned as an XML to the ajax request to fill form fields.
     * XML attribute names must have the same name as the HTML form field names.
     *
     * @param integer $ImgId image id
     */
    public function edit($ImgId)
    {
        // TODO: use DOM functions instead of string to create xml
        $sql = 'SELECT Id, ImgFolder, ImgName, ImgDateManual, ImgTechInfo, FilmTypeId, RatingId,
			DateAdded, LastChange, ImgDesc, ImgTitle, Public, DatePublished,
			ImgDateOriginal, ImgLat, ImgLng, ShowLoc, CountryId
			FROM Images	WHERE Id = :ImgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':ImgId', $ImgId);
        $stmt->execute();
        $strXml = '<?xml version="1.0" encoding="UTF-8"?>';
        $strXml .= '<HtmlFormData xml:lang="de-CH">';
        // image data
        $strXml .= '<Image';
        foreach ($stmt->fetch() as $key => $val) {
            // each col in db is attribute of xml element Image
            if (strpos($key, 'Date') !== false && $key !== 'ImgDateManual' && $val !== null && $val !== '') {
                $strXml .= ' '.$key.'="'.date('d.m.Y H:i:s', $val).'"';
            } elseif ($key === 'LastChange' && $val !== null && $val !== '') {
                $strXml .= ' '.$key.'="'.date('d.m.Y H:i:s', $val).'"';
            } else {
                $strXml .= ' '.$key.'="'.htmlspecialchars($val, ENT_QUOTES, 'UTF-8').'"';
            }
        }
        $strXml .= '/>';
        // themes
        $sql = 'SELECT ThemeId FROM Images_Themes WHERE ImgId = :ImgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':ImgId', $ImgId);
        $stmt->execute();
        $strXml .= '<Themes Id="'.$ImgId.'">';
        foreach ($stmt->fetchAll() as $row) {
            $strXml .= '<Theme Id="'.$row['ThemeId'].'"/>';
        }
        $strXml .= '</Themes>';
        // keywords
        $sql = 'SELECT Name, KeywordId FROM Images_Keywords IK
			INNER JOIN Keywords ON IK.KeywordId = Keywords.Id
			WHERE ImgId = :ImgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':ImgId', $ImgId);
        $stmt->execute();
        $strXml .= '<Keywords Id="'.$ImgId.'">';
        foreach ($stmt->fetchAll() as $row) {
            $strXml .= '<Keyword Id="'.$row['KeywordId'].'" Name="'.$row['Name'].'"/>';
        }
        $strXml .= '</Keywords>';
        // species
        $sql = 'SELECT isn.ScientificNameId, isn.SexId,
                sn.NameDe, sn.NameEn, sn.NameLa, 
                ss.NameEn SexEn, ss.NameDe SexDe 
			FROM Images_ScientificNames isn 
			INNER JOIN ScientificNames sn ON isn.ScientificNameId = sn.Id
			INNER JOIN Sexes ss ON isn.SexId = ss.Id
			WHERE ImgId = :ImgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':ImgId', $ImgId);
        $stmt->execute();
        $strXml .= '<ScientificNames Id="'.$ImgId.'">';
        foreach ($stmt->fetchAll() as $row) {
            $strXml .= '<ScientificName Id="'.$row['ScientificNameId'].'" NameDe="'.htmlspecialchars($row['NameDe'], ENT_QUOTES, 'UTF-8');
            $strXml .= '" NameEn="'.htmlspecialchars($row['NameEn'], ENT_QUOTES, 'UTF-8').
                '" NameLa="'.htmlspecialchars($row['NameLa'], ENT_QUOTES, 'UTF-8').'"';
            $strXml .= ' SexId="'.$row['SexId'].'" SexText="'.htmlspecialchars($row['SexDe'], ENT_QUOTES, 'UTF-8').'"/>';
        }
        $strXml .= '</ScientificNames>';
        // locations
        // TODO: find solution to location name might occur twice but in a different country
        $sql = 'SELECT il.LocationId LocId, l.Name LocName, countryId FROM Images_Locations il
			INNER JOIN Locations l ON il.LocationId = l.Id
			INNER JOIN Locations_Countries lc ON l.Id = lc.LocationId
			WHERE ImgId = :ImgId';
        // AND countryId = ???§
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':ImgId', $ImgId);
        $stmt->execute();
        $strXml .= '<Locations Id="'.$ImgId;
        $arrData = $stmt->fetchAll();
        if (!\count($arrData)) {
            $strXml .= '" CountryId="">';
        }
        foreach ($arrData as $row) {
            if (!isset($countryId)) {
                $countryId = $row['CountryId'];
                $strXml .= '" CountryId="'.$countryId.'">';
            }
            $strXml .= '<Location Id="'.$row['LocId'].'" Name="'.htmlspecialchars($row['LocName'], ENT_QUOTES, 'UTF-8').'"/>';
        }
        $strXml .= '</Locations>';
        $strXml .= '</HtmlFormData>';
        header('Content-Type: text/xml; charset=UTF-8');
        echo $strXml;
    }

    /**
     * Update image data.
     * @param string $XmlData
     */
    public function updateAll($XmlData)
    {
        // TODO: more generic update
        // TODO: after UpdateAll send updated data back to browser (like Edit), for ex. LocationId would be updated
        $xml = new DOMDocument();
        $xml->loadXML($XmlData);
        $this->beginTransaction();
        // update images
        $attributes = $xml->getElementsByTagName('Image')->item(0)->attributes;
        $imgId = $attributes->getNamedItem('Id')->nodeValue;
        $count = 0;
        $sql = 'UPDATE Images SET';
        foreach ($attributes as $attr) {
            $sql .= ' '.$this->escapeString($attr->nodeName)." = :Val$count,";
            $count++;
        }
        $sql = rtrim($sql, ',');
        $sql .= ' WHERE Id = :imgId';
        $stmt = $this->db->prepare($sql);
        $count = 0;
        foreach ($attributes as $attr) {
            if (strpos($attr->nodeName, 'Date') !== false && $attr->nodeName !== 'ImgDateManual') {
                if ($attr->nodeValue !== '' && strtotime($attr->nodeValue)) {
                    $stmt->bindParam(":Val$count", strtotime($attr->nodeValue));
                }
            } else {
                $stmt->bindParam(":Val$count", $attr->nodeValue);
            }
            $count++;
        }
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = 'UPDATE Images SET LastChange = '.time().' WHERE Id = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        // update images_keywords
        $sql = 'DELETE FROM Images_Keywords WHERE ImgId = :imgId';   // Delete all first -> add current keywords back. User might have deleted keyword, which would not be transmitted
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        if ($xml->getElementsByTagName('Keywords')->length > 0) {
            $el = $xml->getElementsByTagName('Keywords')->item(0);
            $imgId = $el->getAttribute('Id');
            $children = $el->childNodes;
            $sql1 = 'INSERT INTO Images_Keywords (ImgId, KeywordId) VALUES (:imgId, :keywordId)';
            $stmt1 = $this->db->prepare($sql1);
            $stmt1->bindParam(':imgId', $imgId);
            $stmt1->bindParam(':keywordId', $keywordId);
            $sql2 = 'INSERT INTO Keywords (Id, Name) VALUES (NULL, :Name)';
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->bindParam(':Name', $keyword);
            $sql3 = 'SELECT KeywordId FROM Images_Keywords WHERE ImgId = :imgId AND KeywordId = :keywordId';
            $stmt3 = $this->db->prepare($sql3);
            $stmt3->bindParam(':imgId', $imgId);
            $stmt3->bindParam(':keywordId', $keywordId);
            $sql4 = 'SELECT Id FROM Keywords WHERE Name = :Name';
            $stmt4 = $this->db->prepare($sql4);
            $stmt4->bindParam(':Name', $keyword);
            /** @var DOMElement[] $children */
            foreach ($children as $child) {
                $keywordId = $child->getAttribute('Id');
                $keyword = $child->getAttribute('Name');
                // 1. Insert into keyword table first if new keyword,e.g no id. and
                // use (returned) id for table Images_Keywords.
                // Note: Its possible that there is no id posted, but keyword is already in db -> check name first
                if ($keywordId === '' || $keywordId === 'null') { // new?
                    $stmt4->execute();
                    if ($row = $stmt4->fetch()) {
                        $keywordId = $row['Id'];
                    } else {
                        $stmt2->execute();
                        $keywordId = $this->db->lastInsertId();
                    }
                    $stmt4->closeCursor();
                }
                // 2. Check if keyword was not inserted previously
                // because user might have the same keyword twice in the div-list.
                $stmt3->execute();
                // 3. Insert keyword id into table Images_Keywords
                if (!$row = $stmt3->fetch()) {
                    $stmt3->closeCursor();
                    $stmt1->execute();
                }
            }
        }
        // update images_themes
        $el = $xml->getElementsByTagName('Themes')->item(0);
        $sql = 'DELETE FROM Images_Themes WHERE ImgId = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $children = $el->childNodes;
        $sql = 'INSERT INTO Images_Themes (ImgId, ThemeId) VALUES (:imgId, :themeId)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->bindParam(':themeId', $themeId);
        /** @var DOMElement[] $children */
        foreach ($children as $child) {
            $themeId = $child->getAttribute('Id');
            $stmt->execute();
        }
        // update Images_ScientificNames. Note: not every image has a species.
        $sql = 'DELETE FROM Images_ScientificNames WHERE ImgId = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        if ($xml->getElementsByTagName('ScientificNames')->length > 0) {
            $el = $xml->getElementsByTagName('ScientificNames')->item(0);
            $children = $el->childNodes;
            $sql = 'INSERT INTO Images_ScientificNames (ImgId, ScientificNameId, SexId) VALUES (:imgId, :speciesId, :sexId)';
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':imgId', $imgId);
            $stmt->bindParam(':speciesId', $speciesId);
            $stmt->bindParam(':sexId', $sexId);
            foreach ($children as $child) {
                $speciesId = $child->getAttribute('Id');
                $sexId = $child->getAttribute('SexId');
                $stmt->execute();
            }
        }
        // update locations
        // An image can have several locations. A location name can occur once per country
        // -> enables filtering locations by country
        // All queries have to be executed singledly, because resulting records are used as input. -> do not put in one transaction
        // TODO: consequences for multiuser ?
        $sql = 'DELETE FROM Images_Locations WHERE ImgId = :imgId';   // always remove first before setting new locs, maybe user simply wants to remove locs
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        if ($xml->getElementsByTagName('Locations')->length > 0) {
            $el = $xml->getElementsByTagName('Locations')->item(0);
            $countryId = $el->getAttribute('CountryId');
            $children = $el->childNodes;
            foreach ($children as $child) {
                $locationId = $child->getAttribute('Id');
                // 1. Check if location name is already in table locations, if not, insert it.
                // Use (returned) id for table Images_Locations and Locations_Countries.
                if ($locationId === '' || $locationId === 'null') { // new? location from map or input field
                    $name = $child->getAttribute('Name');
                    $sql = 'SELECT Id FROM Locations l
						INNER JOIN Locations_Countries lc ON lc.LocationId = l.Id
						WHERE Name = :name AND CountryId = :countryId';
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':countryId', $countryId);
                    $stmt->execute();
                    if ($row = $stmt->fetch()) {
                        $locationId = $row['Id'];
                    } else {
                        $sql = 'INSERT INTO Locations (Id, Name) VALUES (NULL, :name)';
                        $stmt = $this->db->prepare($sql);
                        $stmt->bindParam(':name', $name);
                        $stmt->execute();
                        $locationId = $this->db->lastInsertId();
                        $sql = 'INSERT INTO Locations_Countries (LocationId, CountryId) VALUES (:locationId, :countryId)';
                        $stmt = $this->db->prepare($sql);
                        $stmt->bindParam(':locationId', $locationId);
                        $stmt->bindParam(':countryId', $countryId);
                        $stmt->execute();
                    }
                }
                // 2. Check if location was not inserted previously into Images_Locations,
                // because user might have the same location twice in the list.
                $sql = 'SELECT LocationId FROM Images_Locations WHERE ImgId = :imgId AND LocationId = :locationId';
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':imgId', $imgId);
                $stmt->bindParam(':locationId', $locationId);
                $stmt->execute();
                // 3. Insert location into table Images_Locations
                if (!$row = $stmt->fetch()) {
                    $sql = 'INSERT INTO Images_Locations (ImgId, LocationId) VALUES (:imgId, :locationId)';
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':imgId', $imgId);
                    $stmt->bindParam(':locationId', $locationId);
                    $stmt->execute();
                }
            }
        }
        if ($this->commit()) {
            echo 'success';
        } else {
            print_r($this->db->ErrorInfo());
            $this->rollback();
            echo 'failed';
        }
    }

    /**
     * Delete image data from database.
     *
     * @param integer $imgId image id
     */
    public function delete($imgId)
    {
        $this->beginTransaction();
        $sql = 'DELETE FROM Images WHERE Id = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = 'DELETE FROM Exif WHERE ImgId = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = 'DELETE FROM Xmp WHERE ImgId = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = 'DELETE FROM Images_ScientificNames WHERE ImgId = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = 'DELETE FROM Images_Keywords WHERE ImgId = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = 'DELETE FROM Images_Themes WHERE ImgId = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = 'DELETE FROM Images_Locations WHERE ImgId = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        if ($this->commit()) {
            echo 'success';
        } else {
            $this->rollback();
            echo 'failed';
        }
    }

    /**
     * Insert XMP data into the database.
     * Inserts Adobe Lightroom XMP crop information into the table Xmp.
     * @param int $imgId image id
     * @param array $exifData
     * @return bool
     */
    public function insertXmp($imgId, $exifData): bool
    {
        $sql = 'INSERT OR REPLACE INTO Xmp (ImgId, CropTop, CropLeft, CropBottom, CropRight, CropAngle, SyncDate) 
            VALUES (:imgId, :cropTop, :cropLeft, :cropBottom, :cropRight, :cropAngle, CURRENT_TIMESTAMP)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->bindParam(':cropTop', $exifData['CropTop']);
        $stmt->bindParam(':cropLeft', $exifData['CropLeft']);
        $stmt->bindParam(':cropBottom', $exifData['CropBottom']);
        $stmt->bindParam(':cropRight', $exifData['CropRight']);
        $stmt->bindParam(':cropAngle', $exifData['CropAngle']);

        return $stmt->execute();
    }

    /**
     * Returns the image name and folder.
     * @param int $imgId image id
     * @return string
     */
    public function getImageSrc($imgId): string
    {
        $sql = 'SELECT ImgFolder, ImgName FROM Images WHERE Id = :imgId';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row['ImgFolder'].'/'.$row['ImgName'];
    }

    /**
     * Executes the exif service and returns the read image exif and xmp data.
     * @param string $imgSrc image name and folder
     * @return array
     */
    public function getExif($imgSrc): array
    {
        // TODO: use https://github.com/tsmgeek/ExifTool_PHP_Stayopen
        $img = $this->folderImageOriginal.'/'.$imgSrc;
        $exifService = new ExifService($this->exiftool);

        return $exifService->getData($img);
    }

    /**
     * Insert exif data read from image into fotodb.
     * Returns true on success or false on failure.
     *
     * @param int $imgId image id
     * @param array $exifData exif data
     * @return bool
     * @internal param int $img image database id
     */
    public function insertExif($imgId, $exifData)
    {
        // note: Scanned slides have a lot of empty exif data
        $arrExif = $this->mapExif($exifData);
        if (\count($arrExif) > 0) {
            $sqlTemp = '';
            $sql = 'INSERT OR REPLACE INTO Exif (ImgId,';   // deletes row first if conflict occurs
            foreach ($arrExif as $key => $val) {   // column names
                $sqlTemp .= "$key,";
            }
            $sql .= rtrim($sqlTemp, ',').', SyncDate) VALUES (:ImgId,';
            $sqlTemp = '';
            foreach ($arrExif as $key => $val) {   // column data
                if (strpos($key, 'Date') !== false) {
                    if ($val !== '' && strtotime($val)) {
                        $sqlTemp .= "'".$this->escapeString(strtotime($val))."',";
                    } else {
                        $sqlTemp .= 'NULL,';
                    }
                } else {
                    $sqlTemp .= "'".$this->escapeString($val)."',";
                }
            }
            $sql .= rtrim($sqlTemp, ',').', CURRENT_TIMESTAMP);';
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':ImgId', $imgId);
            $stmt->execute();
            // Use exif DateTimeOriginal also as column value in the Images table, but
            // not in case of scanned slides which have only date of scanning.
            if ($arrExif['Model'] !== 'Nikon SUPER COOLSCAN 5000 ED' && $arrExif['DateTimeOriginal'] !== '') {
                $sql = 'UPDATE Images SET ImgDateOriginal = :Date WHERE Id = :ImgId';
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':ImgId', $imgId);
                $stmt->bindParam(':Date', strtotime($arrExif['DateTimeOriginal']));
                $stmt->execute();
            }
            if ($arrExif['GPSLatitude'] !== '') {
                $sql = 'UPDATE Images SET ImgLat = :lat, ImgLng = :lng WHERE Id = :imgId';
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':imgId', $imgId);
                $stmt->bindParam(':lat', $arrExif['GPSLatitude']);
                $stmt->bindParam(':lng', $arrExif['GPSLongitude']);
                $stmt->execute();
            }

            return true;
        }

        return false;
    }

    /**
     * Load form field data and output it.
     */
    public function loadData(): void
    {
        $data = $_POST['FldName'] ?? $_GET['FldName'] ?? '';
        switch ($data) {
            case 'Location':
                $countryId = (isset($_POST['CountryId']) && $_POST['CountryId'] !== '') ? $_POST['CountryId'] : null;
                $sql = 'SELECT L.Id, L.Name LocName FROM Locations L';
                if ($countryId !== null) {
                    $sql .= ' LEFT JOIN Locations_Countries LC ON L.Id = LC.LocationId
					WHERE CountryId = :countryId';
                }
                $sql .= ' ORDER BY Name ASC';
                $stmt = $this->db->prepare($sql);
                if ($countryId !== null) {
                    $stmt->bindParam(':countryId', $countryId);
                }
                $stmt->execute();
                while ($row = $stmt->fetch()) {
                    echo '<option value="'.$row['Id'].'">'.$row['LocName'].'</option>';
                }
                break;
            case 'KeywordName':
                $query = (isset($_GET['Name']) && $_GET['Name'] !== '') ? $_GET['Name'] : '';
                $limit = (isset($_GET['count']) && preg_match('/^[0-9]+$/', $_GET['count']) === 1) ? $_GET['count'] : 50;
                $offset = (isset($_GET['start']) && preg_match('/^[0-9]+$/', $_GET['start']) === 1) ? $_GET['start'] : 0;
                $sql = "SELECT Id, Name FROM Keywords WHERE Name LIKE '%'||:query||'%' ORDER BY Name ASC LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':query', $query);
                $stmt->bindParam(':limit', $limit);
                $stmt->bindParam(':offset', $offset);
                $stmt->execute();
                $arr = $stmt->fetchAll();
                echo json_encode($arr);
                break;
            case 'ScientificName':
                $query = (isset($_POST['Val']) && $_POST['Val'] !== '') ? $_POST['Val'] : '';
                $colName = (isset($_POST['ColName']) && preg_match('/^\w+$/', $_POST['ColName']) === 1) ? $_POST['ColName'] : 'NameDe';
                $limit = (isset($_POST['count']) && preg_match('/[0-9]+/', $_POST['count']) !== false) ? $_POST['count'] : 50;
                $offset = (isset($_POST['start']) && preg_match('/[0-9]+/', $_POST['start']) !== false) ? $_POST['start'] : 0;
                $sql = "SELECT Id, NameDe, NameEn, NameLa, ThemeId FROM ScientificNames WHERE $colName LIKE '%'||:query||'%' LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':query', $query);
                $stmt->bindParam(':limit', $limit);
                $stmt->bindParam(':offset', $offset);
                $stmt->execute();
                $arr = $stmt->fetchAll();
                $arr = ['identifier' => 'Id', 'items' => $arr];
                echo json_encode($arr);
                break;
        }
    }

    /**
     * Adds a SQL GROUP_CONCAT function
     * Method used in the SQLite createAggregate function to implement SQL GROUP_CONCAT
     * which is not supported by PDO.
     * @param string $Context
     * @param string $RowId
     * @param string $String
     * @param bool [$Unique]
     * @param string [$Separator]
     * @return
     */
    function groupConcatStep($Context, $RowId, $String, $Unique = false, $Separator = ', ')
    {
        if ($Context) {
            if ($Unique) {
                if (strpos($Context, $String) !== false) {
                    return $Context;
                }

                return $Context.$Separator.$String;
            }

            return $Context.$Separator.$String;
        }

        return $String;
    }

    function groupConcatFinalize($Context)
    {
        return $Context;
    }

    /**
     * Adds the PHP strtotime function to PDO SQLite.
     * @param string $Context
     * @return string
     */
    function strToTime($Context)
    {
        if (\strlen($Context) > 4) {
            return strtotime($Context);
        }

        if (preg_match('/\d{4}/', $Context)) {
            return strtotime($Context.'-01-01');
        }

        return null;
    }

    /**
     * Map exif data to an array with keys matching database columns to insert data into.
     * Depending on the type, either image data from the original image is read (other analog directory)
     * or exif data from the effectively selected image is used.
     * @param array $arrExif
     * @return array
     */
    public function mapExif($arrExif)
    {
        $data = [];
        $data['ImageWidth'] = $arrExif['XMP']['ImageWidth'];    // exif does not report correct image size
        $data['ImageHeight'] = $arrExif['XMP']['ImageHeight'];
        $data['DateTimeOriginal'] = \array_key_exists('DateTimeOriginal', $arrExif['EXIF']) ? $arrExif['EXIF']['DateTimeOriginal'] : '';
        $data['ExposureTime'] = \array_key_exists('ExposureTime', $arrExif['EXIF']) ? $arrExif['EXIF']['ExposureTime'] : '';
        $data['FNumber'] = \array_key_exists('FNumber', $arrExif['EXIF']) ? $arrExif['EXIF']['FNumber'] : '';
        $data['ISO'] = \array_key_exists('ISO', $arrExif['EXIF']) ? $arrExif['EXIF']['ISO'] : '';
        $data['ExposureProgram'] = \array_key_exists('ExposureProgram', $arrExif['EXIF']) ? $arrExif['EXIF']['ExposureProgram'] : '';
        $data['MeteringMode'] = \array_key_exists('MeteringMode', $arrExif['EXIF']) ? $arrExif['EXIF']['MeteringMode'] : '';
        $data['Flash'] = \array_key_exists('Flash', $arrExif['EXIF']) ? $arrExif['EXIF']['Flash'] : '';
        $data['FocusDistance'] = \array_key_exists('FocusDistance', $arrExif['MakerNotes']) ? $arrExif['MakerNotes']['FocusDistance'] : '';
        if (\array_key_exists('GPSPosition', $arrExif['EXIF'])) {
            $arr = explode(',', $arrExif['EXIF']['GPSPosition']);
            $data['GPSLatitude'] = str_replace('+', '', $arr[0]);
            $data['GPSLongitude'] = str_replace('+', '', $arr[1]);
        } elseif (\array_key_exists('GPSLatitude', $arrExif['EXIF'])) {
            $data['GPSLatitude'] = $arrExif['EXIF']['GPSLatitudeRef'] === 'South' ? abs($arrExif['EXIF']['GPSLatitude']) * -1 : $arrExif['EXIF']['GPSLatitude'];
            $data['GPSLongitude'] = $arrExif['EXIF']['GPSLongitudeRef'] === 'West' ? abs(
                    $arrExif['EXIF']['GPSLongitude']
                ) * -1 : $arrExif['EXIF']['GPSLongitude'];
        } else {
            $data['GPSLatitude'] = '';
            $data['GPSLongitude'] = '';
        }
        $data['GPSAltitude'] = \array_key_exists('GPSAltitude', $arrExif['EXIF']) ? $arrExif['EXIF']['GPSAltitude'] : '';
        $data['GPSAltitudeRef'] = \array_key_exists('GPSAltitudeRef', $arrExif['EXIF']) ? $arrExif['EXIF']['GPSAltitudeRef'] : '';
        $data['LensSpec'] = \array_key_exists('LensSpec', $arrExif['EXIF']) ? $arrExif['EXIF']['LensSpec'] : '';
        $data['VibrationReduction'] = \array_key_exists('VibrationReduction', $arrExif['MakerNotes']) ? $arrExif['MakerNotes']['VibrationReduction'] : '';
        foreach ($arrExif['Files'] as $file) {
            if (strtolower($file['FileType']) !== 'xmp') {
                $data['FileType'] = $file['FileType'];
                $data['FileSize'] = $file['FileSize'];
            }
        }
        $data['Lens'] = \array_key_exists('Lens', $arrExif['EXIF']) ? $arrExif['EXIF']['Lens'] : '';
        $data['LensSpec'] = \array_key_exists('LensID', $arrExif['Composite']) ? $arrExif['Composite']['LensID'] : '';
        $data['FocalLength'] = \array_key_exists('FocalLength', $arrExif['EXIF']) ? $arrExif['EXIF']['FocalLength'] : '';
        $data['Make'] = \array_key_exists('Make', $arrExif['EXIF']) ? $arrExif['EXIF']['Make'] : 'Nikon';
        $data['Model'] = \array_key_exists('Model', $arrExif['EXIF']) ? $arrExif['EXIF']['Model'] : 'Nikon SUPER COOLSCAN 5000 ED';

        return $data;
    }

    /**
     * Creates the database structure.
     * @return void
     */
    private function createStructure()
    {
        $sql = 'BEGIN;
            create table Countries
            (
                Id INTEGER not null
                    primary key,
                NameEn VARCHAR2,
                NameDe VARCHAR2
            )
            ;
            
            create table FilmTypes
            (
                Id INTEGER not null
                    primary key,
                Name VARCHAR2,
                Code VARCHAR2
            )
            ;
            
            create table Images
            (
                Id INTEGER not null
                    primary key,
                ImgFolder VARCHAR2,
                ImgName VARCHAR2,
                ImgDateManual VARCHAR2,
                ImgTechInfo VARCHAR2,
                FilmTypeId INTEGER,
                RatingId INTEGER,
                DateAdded INTEGER,
                LastChange INTEGER,
                ImgDesc VARCHAR2,
                ImgTitle VARCHAR2,
                Public INTEGER,
                DatePublished INTEGER,
                ImgDateOriginal INTEGER,
                ImgLat FLOAT,
                ImgLng FLOAT,
                ShowLoc INTEGER default 1,
                CountryId INTEGER
            )
            ;
            
            create table Exif
            (
                Make VARCHAR2,
                Model VARCHAR2,
                ImageWidth INTEGER,
                ImageHeight INTEGER,
                FileSize VARCHAR2,
                DateTimeOriginal INTEGER,
                ExposureTime VARCHAR2,
                FNumber INTEGER,
                ISO INTEGER,
                ExposureProgram VARCHAR2,
                MeteringMode VARCHAR2,
                Flash VARCHAR2,
                FocusDistance NUMERIC,
                ImgId INTEGER not null
                    constraint Exif_Images_Id_fk
                        references Images,
                GPSLatitude FLOAT,
                GPSLongitude FLOAT,
                GPSAltitude INTEGER,
                GPSAltitudeRef INTEGER,
                LensSpec VARCHAR,
                VibrationReduction TEXT,
                FileType VARCHAR,
                Lens VARCHAR,
                FocalLength VARCHAR,
                SyncDate TEXT default null
            )
            ;
            
            create unique index Exif_ImgId_uindex
                on Exif (ImgId)
            ;
            
            create table Images_Keywords
            (
                ImgId INTEGER not null,
                KeywordId INTEGER not null
            )
            ;
            
            create table Images_Locations
            (
                ImgId INTEGER,
                LocationId INTEGER
            )
            ;
            
            create table Images_ScientificNames
            (
                ImgId INTEGER not null,
                ScientificNameId INTEGER not null,
                SexId INTEGER not null,
                primary key (ImgId, ScientificNameId)
            )
            ;
            
            create table Images_Themes
            (
                ImgId INTEGER not null,
                ThemeId INTEGER not null
            )
            ;
            
            create table Keywords
            (
                Id INTEGER not null
                    primary key,
                Name VARCHAR2
            )
            ;
            
            create table Locations
            (
                Id INTEGER not null
                    primary key,
                Name VARCHAR(1024)
            )
            ;
            
            create table Locations_Countries
            (
                LocationId INTEGER,
                CountryId INTEGER
            )
            ;
            
            create table Rating
            (
                Id INTEGER not null
                    primary key,
                Name VARCHAR2
            )
            ;
            
            create table ScientificNames
            (
                Id INTEGER not null
                    primary key,
                NameDe VARCHAR2,
                NameEn VARCHAR2,
                NameLa VARCHAR2,
                ThemeId INTEGER default null
            )
            ;
            
            create table Sexes
            (
                Id INTEGER not null
                    primary key,
                NameEn VARCHAR2,
                NameDe VARCHAR2,
                Symbol TEXT
            )
            ;
            
            create table SubjectAreas
            (
                Id INTEGER not null
                    primary key,
                NameDe VARCHAR,
                NameEn VARCHAR
            )
            ;
            
            create table Themes
            (
                Id INTEGER not null
                    primary key,
                NameDe VARCHAR2,
                SubjectAreaId INTEGER,
                NameEn VARCHAR
            )
            ;
            
            create table Xmp
            (
                ImgId INT
                    constraint Xmp_Images_Id_fk
                        references Images,
                CropTop FLOAT,
                CropLeft FLOAT,
                CropBottom FLOAT,
                CropRight FLOAT,
                CropAngle FLOAT,
                SyncDate TEXT default null
            )
            ;
            
            create unique index Xmp_ImgId_uindex
                on Xmp (ImgId)
            ;
            
            create table searchIndex
            (
                id INTEGER
                    primary key,
                word TEXT
                    unique
                    on conflict ignore
            )
            ;
            
            create table searchOccurrences
            (
                wordId INTEGER not null,
                recordId INTEGER not null
            )
            ;
            
            create table sqlite_stat1
            (
                tbl,
                idx,
                stat
            )
            ;
            COMMIT;';
        $this->db->exec($sql);
        print_r($this->db->errorInfo());
    }

    /**
     * @return string
     */
    private function getWebRoot()
    {
        return $this->webroot;
    }
}