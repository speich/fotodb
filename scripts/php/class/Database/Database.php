<?php

namespace PhotoDatabase\Database;

use DOMDocument;
use DOMElement;
use PDO;
use PDOException;
use SQLite3;
use PhotoDatabase\ExifService;


/**
 * Class to work with SQLite databases.
 * Creates the photo DB.
 *
 */
class Database
{
    /** @var PDO $db db instance of SQLite */
    public $db = null;
    // paths are always appended to webroot ('/' or a subfolder) and start therefore with a foldername
    // and not with a slash, but end with a slash
    private $dbName = "FotoDb.sqlite";
    // TODO: get these two paths from config.
    private $PathImg = null;  // path relative to web root of private db images
    private $folderImageOriginal = null;    // absolute path where image originals are stored*/
    private $ExecTime = 300;
    protected $HasActiveTransaction = false;    // keep track of open transactions
    private $webroot = '/';
    private $dbPath = '/../../../../dbprivate/dbfiles/';    // relative to this class

    /**
     * @constructor
     */
    public function __construct($config)
    {
        set_time_limit($this->ExecTime);
        $this->PathImg = $config->paths->imagesWebRoot;
        $this->folderImageOriginal = $config->paths->imagesOriginal;
        $this->exiftool = $config->paths->exifTool;
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
        if (is_null($this->db)) {   // check if not already connected
            try {
                $dbFile = __DIR__.$this->dbPath.$this->dbName;
                $this->db = new PDO('sqlite:'.$dbFile);
                $isCreated = file_exists($dbFile);
                if (!$isCreated) {
                    $this->createStructure();
                }
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
                // Do every time you connect since they are only valid during connection (not permanent)
                $this->db->sqliteCreateAggregate('GROUP_CONCAT', [$this, 'groupConcatStep'],
                    [$this, 'groupConcatFinalize']);
                $this->db->sqliteCreateFunction('STRTOTIME', [$this, 'strToTime']);
//				$this->Db->sqliteCreateFunction('LOCALE', array($this, 'GetSortOrder'), 1);
                $this->db->exec("pragma short_column_names = 1");
            } catch (PDOException $Error) {
                echo $Error->getMessage();

                return null;
            }
        }

        return $this->db;
    }

    function sqlite_escape_string($string)
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
    public function BeginTransaction()
    {
        if ($this->HasActiveTransaction === true) {
            return false;
        } else {
            $this->HasActiveTransaction = $this->db->beginTransaction();

            return $this->HasActiveTransaction;
        }
    }

    /**
     * Comit transaction and set flag to false.
     * @return bool
     */
    public function Commit()
    {
        $this->HasActiveTransaction = false;

        return $this->db->commit();
    }

    /**
     * Rollback transaction and set flag to false.
     * @return bool
     */
    function Rollback()
    {
        $this->HasActiveTransaction = false;

        return $this->db->rollback();
    }

    /**
     * Returns the file name of the database.
     * @return string
     */
    public function GetDbName()
    {
        return $this->dbName;
    }

    /**
     * Provides access to the different paths in the FotoDB project.
     * @return string
     * @param string $Name
     */
    public function GetPath($Name)
    {
        $Path = '';
        switch ($Name) {
            case 'WebRoot':
                $Path = $this->GetWebRoot();
                break;   // redundant, but for convenience
            case 'Db':
                $Path = __DIR__.$this->dbPath;
                break;
            case 'Img':
                $Path = $this->PathImg;
                break;
            case 'ImgOriginal':
                $Path = $this->folderImageOriginal;
        }

        return $Path;   // pdo functions need full path to work with subfolders on windows
    }

    /**
     * Insert new image data from form and from exif data.
     *
     * This method is only called once, when the image is selected by the user for the first time.
     * @param string $imgSrc image file including web root path
     * @return string XML file
     */
    public function Insert($img)
    {
        $ImgFolder = str_replace($this->GetWebRoot().ltrim($this->GetPath('Img'), '/'), '',
            $img);   // remove web images folder path part
        $ImgName = substr($ImgFolder, strrpos($ImgFolder, '/') + 1);
        $ImgFolder = trim(str_replace($ImgName, '', $ImgFolder), '/');
        $Sql = "INSERT INTO Images (Id, ImgFolder, ImgName, DateAdded, LastChange)
			VALUES (NULL, :ImgFolder, :ImgName,".time().",".time().")";
        $this->BeginTransaction();
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgName', $ImgName);
        $Stmt->bindParam(':ImgFolder', $ImgFolder);
        $Stmt->execute();
        $imgId = $this->db->lastInsertId();
        $imgSrc = $ImgFolder.'/'.$ImgName;
        // insert exif data
        $exifData = $this->getExif($imgSrc);
        if (!$this->InsertExif($imgId, $exifData)) {
            echo 'failed';

            return false;
        }
        if (array_key_exists('XMP', $exifData) && !$this->insertXmp($imgId, $exifData['XMP'])) {
            echo 'failed';

            return false;
        }
        $Sql = "SELECT Id, ImgFolder, ImgName, ImgDateManual,	ImgTechInfo, FilmTypeId, RatingId,
			DateAdded, LastChange, ImgDesc,	ImgTitle, Public, DatePublished, ImgDateOriginal, ImgLat, ImgLng, ShowLoc, CountryId
			FROM Images WHERE Id = :ImgId";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $imgId);
        $Stmt->execute();
        $this->Commit();
        $strXml = '<?xml version="1.0" encoding="UTF-8"?>';
        $strXml .= '<HtmlFormData xml:lang="de-CH">';
        // image data
        $strXml .= '<Image';
        foreach ($Stmt->fetch(PDO::FETCH_ASSOC) as $Key => $Val) {
            // each col in db is attribute of xml element Image
            if (strpos($Key, 'Date') !== false && $Key != 'ImgDateManual' && !is_null($Val) && $Val != '') {
                $strXml .= ' '.$Key.'="'.date("d.m.Y H:i:s", $Val).'"';
            } else if ($Key == 'LastChange' && !is_null($Val) && $Val != '') {
                $strXml .= ' '.$Key.'="'.date("d.m.Y H:i:s", $Val).'"';
            } else {
                $strXml .= ' '.$Key.'="'.$Val.'"';
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
    public function Edit($ImgId)
    {
        // TODO: use DOM functions instead of string to create xml
        $Sql = "SELECT Id, ImgFolder, ImgName, ImgDateManual, ImgTechInfo, FilmTypeId, RatingId,
			DateAdded, LastChange, ImgDesc, ImgTitle, Public, DatePublished,
			ImgDateOriginal, ImgLat, ImgLng, ShowLoc, CountryId
			FROM Images	WHERE Id = :ImgId";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        $strXml = '<?xml version="1.0" encoding="UTF-8"?>';
        $strXml .= '<HtmlFormData xml:lang="de-CH">';
        // image data
        $strXml .= '<Image';
        foreach ($Stmt->fetch(PDO::FETCH_ASSOC) as $Key => $Val) {
            // each col in db is attribute of xml element Image
            if (strpos($Key, 'Date') !== false && $Key != 'ImgDateManual' && !is_null($Val) && $Val != '') {
                $strXml .= ' '.$Key.'="'.date("d.m.Y H:i:s", $Val).'"';
            } else if ($Key == 'LastChange' && !is_null($Val) && $Val != '') {
                $strXml .= ' '.$Key.'="'.date("d.m.Y H:i:s", $Val).'"';
            } else {
                $strXml .= ' '.$Key.'="'.htmlspecialchars($Val, ENT_QUOTES, 'UTF-8').'"';
            }
        }
        $strXml .= '/>';
        // themes
        $Sql = "SELECT ThemeId FROM Images_Themes WHERE ImgId = :ImgId";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        $strXml .= '<Themes Id="'.$ImgId.'">';
        foreach ($Stmt->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $strXml .= '<Theme Id="'.$Row['ThemeId'].'"/>';
        }
        $strXml .= '</Themes>';
        // keywords
        $Sql = "SELECT Name, KeywordId FROM Images_Keywords IK
			INNER JOIN Keywords ON IK.KeywordId = Keywords.Id
			WHERE ImgId = :ImgId";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        $strXml .= '<Keywords Id="'.$ImgId.'">';
        foreach ($Stmt->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $strXml .= '<Keyword Id="'.$Row['KeywordId'].'" Name="'.$Row['Name'].'"/>';
        }
        $strXml .= '</Keywords>';
        // species
        $Sql = "SELECT ScientificNameId, NameDe, NameEn, NameLa, SexId, ss.Name SexText 
			FROM Images_ScientificNames isn 
			INNER JOIN ScientificNames sn ON isn.ScientificNameId = sn.Id
			INNER JOIN Sexes ss ON isn.SexId = ss.Id
			WHERE ImgId = :ImgId";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        $strXml .= '<ScientificNames Id="'.$ImgId.'">';
        foreach ($Stmt->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $strXml .= '<ScientificName Id="'.$Row['ScientificNameId'].'" NameDe="'.htmlspecialchars($Row['NameDe'],
                    ENT_QUOTES, 'UTF-8');
            $strXml .= '" NameEn="'.htmlspecialchars($Row['NameEn'], ENT_QUOTES,
                    'UTF-8').'" NameLa="'.htmlspecialchars($Row['NameLa'], ENT_QUOTES, 'UTF-8').'"';
            $strXml .= ' SexId="'.$Row['SexId'].'" SexText="'.htmlspecialchars($Row['SexText'], ENT_QUOTES,
                    'UTF-8').'"/>';
        }
        $strXml .= '</ScientificNames>';
        // locations
        // TODO: find solution to location name might occur twice but in a different country
        $Sql = "SELECT il.LocationId LocId, l.Name LocName, CountryId FROM Images_Locations il
			INNER JOIN Locations l ON il.LocationId = l.Id
			INNER JOIN Locations_Countries lc ON l.Id = lc.LocationId
			WHERE ImgId = :ImgId";
        // AND CountryId = ???§
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        $strXml .= '<Locations Id="'.$ImgId;
        $arrData = $Stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!count($arrData)) {
            $strXml .= '" CountryId="">';
        }
        foreach ($arrData as $Row) {
            if (!isset($CountryId)) {
                $CountryId = $Row['CountryId'];
                $strXml .= '" CountryId="'.$CountryId.'">';
            }
            $strXml .= '<Location Id="'.$Row['LocId'].'" Name="'.htmlspecialchars($Row['LocName'], ENT_QUOTES,
                    'UTF-8').'"/>';
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
    public function UpdateAll($XmlData)
    {
        // TODO: more generic update
        // TODO: after UpdateAll send updated data back to browser (like Edit), for ex. LocationId would be updated
        $Xml = new DOMDocument();
        $Xml->loadXML($XmlData);
        $this->BeginTransaction();
        // update images
        $Attributes = $Xml->getElementsByTagName('Image')->item(0)->attributes;
        $ImgId = $Attributes->getNamedItem('Id')->nodeValue;
        $Count = 0;
        $Sql = "UPDATE Images SET";
        foreach ($Attributes as $Attr) {
            $Sql .= " ".$this->sqlite_escape_string($Attr->nodeName)." = :Val$Count,";
            $Count++;
        }
        $Sql = rtrim($Sql, ',');
        $Sql .= " WHERE Id = :ImgId";
        $Stmt = $this->db->prepare($Sql);
        $Count = 0;
        foreach ($Attributes as $Attr) {
            if (strpos($Attr->nodeName, 'Date') !== false && $Attr->nodeName != 'ImgDateManual') {
                if ($Attr->nodeValue != '' && strtotime($Attr->nodeValue)) {
                    $Stmt->bindParam(":Val$Count", strtotime($Attr->nodeValue));
                }
            } else {
                $Stmt->bindParam(":Val$Count", $Attr->nodeValue);
            }
            $Count++;
        }
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        $Sql = "UPDATE Images SET LastChange = ".time()." WHERE Id = :ImgId";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        // update images_keywords
        $Sql = "DELETE FROM Images_Keywords WHERE ImgId = :ImgId";   // Delete all first -> add current keywords back. User might have deleted keyword, which would not be transmitted
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        if ($Xml->getElementsByTagName('Keywords')->length > 0) {
            $El = $Xml->getElementsByTagName('Keywords')->item(0);
            $ImgId = $El->getAttribute('Id');
            $Children = $El->childNodes;
            $Sql1 = "INSERT INTO Images_Keywords (ImgId, KeywordId) VALUES (:ImgId, :KeywordId)";
            $Stmt1 = $this->db->prepare($Sql1);
            $Stmt1->bindParam(':ImgId', $ImgId);
            $Stmt1->bindParam(':KeywordId', $KeywordId);
            $Sql2 = "INSERT INTO Keywords (Id, Name) VALUES (NULL, :Name)";
            $Stmt2 = $this->db->prepare($Sql2);
            $Stmt2->bindParam(':Name', $Keyword);
            $Sql3 = "SELECT KeywordId FROM Images_Keywords WHERE ImgId = :ImgId AND KeywordId = :KeywordId";
            $Stmt3 = $this->db->prepare($Sql3);
            $Stmt3->bindParam(':ImgId', $ImgId);
            $Stmt3->bindParam(':KeywordId', $KeywordId);
            $Sql4 = "SELECT Id FROM Keywords WHERE Name = :Name";
            $Stmt4 = $this->db->prepare($Sql4);
            $Stmt4->bindParam(':Name', $Keyword);
            /** @var DOMElement[] $Children */
            foreach ($Children as $Child) {
                $KeywordId = $Child->getAttribute('Id');
                $Keyword = $Child->getAttribute('Name');
                // 1. Insert into keyword table first if new keyword,e.g no id. and
                // use (returned) id for table Images_Keywords.
                // Note: Its possible that there is no id posted, but keyword is already in db -> check name first
                if ($KeywordId == '' || $KeywordId == 'null') { // new?
                    $Stmt4->execute();
                    if ($Row = $Stmt4->fetch(PDO::FETCH_ASSOC)) {
                        $KeywordId = $Row['Id'];
                    } else {
                        $Stmt2->execute();
                        $KeywordId = $this->db->lastInsertId();
                    }
                    $Stmt4->closeCursor();
                }
                // 2. Check if keyword was not inserted previously
                // because user might have the same keyword twice in the div-list.
                $Stmt3->execute();
                // 3. Insert keyword id into table Images_Keywords
                if (!$Row = $Stmt3->fetch(PDO::FETCH_ASSOC)) {
                    $Stmt3->closeCursor();
                    $Stmt1->execute();
                }
            }
        }
        // update images_themes
        $El = $Xml->getElementsByTagName('Themes')->item(0);
        $Sql = "DELETE FROM Images_Themes WHERE ImgId = :ImgId";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        $Children = $El->childNodes;
        $Sql = "INSERT INTO Images_Themes (ImgId, ThemeId) VALUES (:ImgId, :ThemeId)";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->bindParam(':ThemeId', $ThemeId);
        /** @var DOMElement[] $Children */
        foreach ($Children as $Child) {
            $ThemeId = $Child->getAttribute('Id');
            $Stmt->execute();
        }
        // update Images_ScientificNames. Note: not every image has a species.
        $Sql = "DELETE FROM Images_ScientificNames WHERE ImgId = :ImgId";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        if ($Xml->getElementsByTagName('ScientificNames')->length > 0) {
            $El = $Xml->getElementsByTagName('ScientificNames')->item(0);
            $Children = $El->childNodes;
            $Sql = "INSERT INTO Images_ScientificNames (ImgId, ScientificNameId, SexId) VALUES (:ImgId, :SpeciesId, :SexId)";
            $Stmt = $this->db->prepare($Sql);
            $Stmt->bindParam(':ImgId', $ImgId);
            $Stmt->bindParam(':SpeciesId', $SpeciesId);
            $Stmt->bindParam(':SexId', $SexId);
            foreach ($Children as $Child) {
                $SpeciesId = $Child->getAttribute('Id');
                $SexId = $Child->getAttribute('SexId');
                $Stmt->execute();
            }
        }
        // update locations
        // An image can have several locations. A location name can occur once per country
        // -> enables filtering locations by country
        // All queries have to be executed singledly, because resulting records are used as input. -> do not put in one transaction
        // TODO: consequences for multiuser ?
        $Sql = "DELETE FROM Images_Locations WHERE ImgId = :ImgId";   // always remove first before setting new locs, maybe user simply wants to remove locs
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':ImgId', $ImgId);
        $Stmt->execute();
        if ($Xml->getElementsByTagName('Locations')->length > 0) {
            $El = $Xml->getElementsByTagName('Locations')->item(0);
            $CountryId = $El->getAttribute('CountryId');
            $Children = $El->childNodes;
            foreach ($Children as $Child) {
                $LocationId = $Child->getAttribute('Id');
                // 1. Check if location name is already in table locations, if not, insert it.
                // Use (returned) id for table Images_Locations and Locations_Countries.
                if ($LocationId == '' || $LocationId == 'null') { // new? location from map or input field
                    $Name = $Child->getAttribute('Name');
                    $Sql = "SELECT Id FROM Locations l
						INNER JOIN Locations_Countries lc ON lc.LocationId = l.Id
						WHERE Name = :Name AND CountryId = :CountryId";
                    $Stmt = $this->db->prepare($Sql);
                    $Stmt->bindParam(':Name', $Name);
                    $Stmt->bindParam(':CountryId', $CountryId);
                    $Stmt->execute();
                    if ($Row = $Stmt->fetch(PDO::FETCH_ASSOC)) {
                        $LocationId = $Row['Id'];
                    } else {
                        $Sql = "INSERT INTO Locations (Id, Name) VALUES (NULL, :Name)";
                        $Stmt = $this->db->prepare($Sql);
                        $Stmt->bindParam(':Name', $Name);
                        $Stmt->execute();
                        $LocationId = $this->db->lastInsertId();
                        $Sql = "INSERT INTO Locations_Countries (LocationId, CountryId) VALUES (:LocationId, :CountryId)";
                        $Stmt = $this->db->prepare($Sql);
                        $Stmt->bindParam(':LocationId', $LocationId);
                        $Stmt->bindParam(':CountryId', $CountryId);
                        $Stmt->execute();
                    }
                }
                // 2. Check if location was not inserted previously into Images_Locations,
                // because user might have the same location twice in the list.
                $Sql = "SELECT LocationId FROM Images_Locations WHERE ImgId = :ImgId AND LocationId = :LocationId";
                $Stmt = $this->db->prepare($Sql);
                $Stmt->bindParam(':ImgId', $ImgId);
                $Stmt->bindParam(':LocationId', $LocationId);
                $Stmt->execute();
                // 3. Insert location into table Images_Locations
                if (!$Row = $Stmt->fetch(PDO::FETCH_ASSOC)) {
                    $Sql = "INSERT INTO Images_Locations (ImgId, LocationId) VALUES (:ImgId, :LocationId)";
                    $Stmt = $this->db->prepare($Sql);
                    $Stmt->bindParam(':ImgId', $ImgId);
                    $Stmt->bindParam(':LocationId', $LocationId);
                    $Stmt->execute();
                }
            }
        }
        if ($this->Commit()) {
            echo 'success';
        } else {
            print_r($this->db->ErrorInfo());
            $this->RollBack();
            echo 'failed';
        }
    }

    /**
     * Delete image data from database.
     *
     * @param integer $imgId image id
     * @return string message
     */
    public function Delete($imgId)
    {
        $this->BeginTransaction();
        $sql = "DELETE FROM Images WHERE Id = :imgId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = "DELETE FROM Exif WHERE ImgId = :imgId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = "DELETE FROM Xmp WHERE ImgId = :imgId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = "DELETE FROM Images_ScientificNames WHERE ImgId = :imgId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = "DELETE FROM Images_Keywords WHERE ImgId = :imgId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = "DELETE FROM Images_Themes WHERE ImgId = :imgId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        $sql = "DELETE FROM Images_Locations WHERE ImgId = :imgId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':imgId', $imgId);
        $stmt->execute();
        if ($this->Commit()) {
            echo 'success';
        } else {
            $this->RollBack();
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
    public function insertXmp($imgId, $exifData)
    {
        $sql = "INSERT OR REPLACE INTO Xmp (ImgId, CropTop, CropLeft, CropBottom, CropRight, CropAngle, SyncDate) 
            VALUES (:imgId, :cropTop, :cropLeft, :cropBottom, :cropRight, :cropAngle, CURRENT_TIMESTAMP)";
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
    public function getImageSrc($imgId)
    {
        $sql = "SELECT ImgFolder, ImgName FROM Images WHERE Id = :ImgId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':ImgId', $imgId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['ImgFolder'].'/'.$row['ImgName'];
    }

    /**
     * Executes the exif service and returns the read image exif and xmp data.
     * @param string $imgSrc image name and folder
     * @return array
     */
    public function getExif($imgSrc)
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
     * @internal param int $img image database id
     * @return bool
     */
    public function InsertExif($imgId, $exifData)
    {
        // note: Scanned slides have a lot of empty exif data
        $arrExif = $this->mapExif($exifData);
        if (count($arrExif) > 0) {
            $SqlTemp = "";
            $Sql = "INSERT OR REPLACE INTO Exif (ImgId,";   // deletes row first if conflict occurs
            foreach ($arrExif as $Key => $Val) {   // column names
                $SqlTemp .= "$Key,";
            }
            $Sql .= rtrim($SqlTemp, ',').", SyncDate) VALUES (:ImgId,";
            $SqlTemp = "";
            foreach ($arrExif as $Key => $Val) {   // column data
                if (strpos($Key, 'Date') !== false) {
                    if ($Val != '' && strtotime($Val)) {
                        $SqlTemp .= "'".$this->sqlite_escape_string(strtotime($Val))."',";
                    } else {
                        $SqlTemp .= "NULL,";
                    }
                } else {
                    $SqlTemp .= "'".$this->sqlite_escape_string($Val)."',";
                }
            }
            $Sql .= rtrim($SqlTemp, ',').", CURRENT_TIMESTAMP);";
            $Stmt = $this->db->prepare($Sql);
            $Stmt->bindParam(':ImgId', $imgId);
            $Stmt->execute();
            // Use exif DateTimeOriginal also as column value in the Images table, but
            // not in case of scanned slides which have only date of scanning.
            if ($arrExif['Model'] != 'Nikon SUPER COOLSCAN 5000 ED' && $arrExif['DateTimeOriginal'] != '') {
                $Sql = 'UPDATE Images SET ImgDateOriginal = :Date WHERE Id = :ImgId';
                $Stmt = $this->db->prepare($Sql);
                $Stmt->bindParam(':ImgId', $imgId);
                $Stmt->bindParam(':Date', strtotime($arrExif['DateTimeOriginal']));
                $Stmt->execute();
            }
            if ($arrExif['GPSLatitude'] !== '') {
                $Sql = 'UPDATE Images SET ImgLat = :Lat, ImgLng = :Lng WHERE Id = :ImgId';
                $Stmt = $this->db->prepare($Sql);
                $Stmt->bindParam(':ImgId', $imgId);
                $Stmt->bindParam(':Lat', $arrExif['GPSLatitude']);
                $Stmt->bindParam(':Lng', $arrExif['GPSLongitude']);
                $Stmt->execute();
            }

            return true;
        }

        return false;
    }

    /**
     * Load form field data and output it.
     */
    public function LoadData()
    {
        $data = isset($_POST['FldName']) ? $_POST['FldName'] : (isset($_GET['FldName']) ? $_GET['FldName'] : '');
        switch ($data) {
            case 'Location':
                $CountryId = (isset($_POST['CountryId']) && $_POST['CountryId'] != '') ? $_POST['CountryId'] : null;
                $Sql = "SELECT L.Id, L.Name LocName FROM Locations L";
                if (!is_null($CountryId)) {
                    $Sql .= " LEFT JOIN Locations_Countries LC ON L.Id = LC.LocationId
					WHERE CountryId = :CountryId";
                }
                $Sql .= " ORDER BY Name ASC";
                $Stmt = $this->db->prepare($Sql);
                if (!is_null($CountryId)) {
                    $Stmt->bindParam(':CountryId', $CountryId);
                }
                $Stmt->execute();
                while ($Row = $Stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="'.$Row['Id'].'">'.$Row['LocName'].'</option>';
                }
                break;
            case 'KeywordName':
                $Query = (isset($_GET['Name']) && $_GET['Name'] != '') ? $_GET['Name'] : '';
                $Limit = (isset($_GET['count']) && preg_match('/^[0-9]+$/',
                        $_GET['count']) === 1) ? $_GET['count'] : 50;
                $Offset = (isset($_GET['start']) && preg_match('/^[0-9]+$/',
                        $_GET['start']) === 1) ? $_GET['start'] : 0;
                $Sql = "SELECT Id, Name FROM Keywords WHERE Name LIKE '%'||:Query||'%' ORDER BY Name ASC
					LIMIT :Limit OFFSET :Offset";
                $Stmt = $this->db->prepare($Sql);
                $Stmt->bindParam(':Query', $Query);
                $Stmt->bindParam(':Limit', $Limit);
                $Stmt->bindParam(':Offset', $Offset);
                $Stmt->execute();
                $arr = $Stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($arr);
                break;
            case 'ScientificName':
                $Query = (isset($_POST['Val']) && $_POST['Val'] != '') ? $_POST['Val'] : '';
                $ColName = (isset($_POST['ColName']) && preg_match('/^\w+$/',
                        $_POST['ColName']) === 1) ? $_POST['ColName'] : 'NameDe';
                $Limit = (isset($_POST['count']) && preg_match('/[0-9]+/',
                        $_POST['count']) !== false) ? $_POST['count'] : 50;
                $Offset = (isset($_POST['start']) && preg_match('/[0-9]+/',
                        $_POST['start']) !== false) ? $_POST['start'] : 0;
                $Sql = "SELECT Id, NameDe, NameEn, NameLa, ThemeId FROM ScientificNames WHERE $ColName LIKE '%'||:Query||'%' LIMIT :Limit OFFSET :Offset";
                $Stmt = $this->db->prepare($Sql);
                $Stmt->bindParam(':Query', $Query);
                $Stmt->bindParam(':Limit', $Limit);
                $Stmt->bindParam(':Offset', $Offset);
                $Stmt->execute();
                $arr = $Stmt->fetchAll(PDO::FETCH_ASSOC);
                $arr = ['identifier' => 'Id', 'items' => $arr];
                echo json_encode($arr);
                break;
        }
    }

    /**
     * Adds a SQL GROUP_CONCAT function
     * Method used in the SQLite createAggregate function to implement SQL GROUP_CONCAT
     * which is not supported by PDO.
     * @return
     * @param string $Context
     * @param string $RowId
     * @param string $String
     * @param bool [$Unique]
     * @param string [$Separator]
     */
    function groupConcatStep($Context, $RowId, $String, $Unique = false, $Separator = ", ")
    {
        if ($Context) {
            if ($Unique) {
                if (strpos($Context, $String) === false) {
                    return $Context.$Separator.$String;
                } else {
                    return $Context;
                }
            } else {
                return $Context.$Separator.$String;
            }
        } else {
            return $String;
        }
    }

    function groupConcatFinalize($Context)
    {
        return $Context;
    }

    /**
     * Adds the PHP strtotime function to PDO SQLite.
     * @return string
     * @param string $Context
     */
    function strToTime($Context)
    {
        if (strlen($Context) > 4) {
            return strtotime($Context);
        } else if (preg_match('/[0-9]{4}/', $Context)) {
            return strtotime($Context."-01-01");
        } else {
            return null;
        }
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
        $data['DateTimeOriginal'] = array_key_exists('DateTimeOriginal',
            $arrExif['EXIF']) ? $arrExif['EXIF']['DateTimeOriginal'] : '';
        $data['ExposureTime'] = array_key_exists('ExposureTime',
            $arrExif['EXIF']) ? $arrExif['EXIF']['ExposureTime'] : '';
        $data['FNumber'] = array_key_exists('FNumber', $arrExif['EXIF']) ? $arrExif['EXIF']['FNumber'] : '';
        $data['ISO'] = array_key_exists('ISO', $arrExif['EXIF']) ? $arrExif['EXIF']['ISO'] : '';
        $data['ExposureProgram'] = array_key_exists('ExposureProgram',
            $arrExif['EXIF']) ? $arrExif['EXIF']['ExposureProgram'] : '';
        $data['MeteringMode'] = array_key_exists('MeteringMode',
            $arrExif['EXIF']) ? $arrExif['EXIF']['MeteringMode'] : '';
        $data['Flash'] = array_key_exists('Flash', $arrExif['EXIF']) ? $arrExif['EXIF']['Flash'] : '';
        $data['FocusDistance'] = array_key_exists('FocusDistance',
            $arrExif['MakerNotes']) ? $arrExif['MakerNotes']['FocusDistance'] : '';
        if (array_key_exists('GPSPosition', $arrExif['EXIF'])) {
            $arr = explode(',', $arrExif['EXIF']['GPSPosition']);
            $data['GPSLatitude'] = str_replace('+', '', $arr[0]);
            $data['GPSLongitude'] = str_replace('+', '', $arr[1]);
        } else if (array_key_exists('GPSLatitude', $arrExif['EXIF'])) {
            $data['GPSLatitude'] = $arrExif['EXIF']['GPSLatitudeRef'] === 'South' ? abs($arrExif['EXIF']['GPSLatitude']) * -1 : $arrExif['EXIF']['GPSLatitude'];
            $data['GPSLongitude'] = $arrExif['EXIF']['GPSLongitudeRef'] === 'West' ? abs($arrExif['EXIF']['GPSLongitude']) * -1 : $arrExif['EXIF']['GPSLongitude'];
        } else {
            $data['GPSLatitude'] = '';
            $data['GPSLongitude'] = '';
        }
        $data['GPSAltitude'] = array_key_exists('GPSAltitude', $arrExif['EXIF']) ? $arrExif['EXIF']['GPSAltitude'] : '';
        $data['GPSAltitudeRef'] = array_key_exists('GPSAltitudeRef',
            $arrExif['EXIF']) ? $arrExif['EXIF']['GPSAltitudeRef'] : '';
        $data['LensSpec'] = array_key_exists('LensSpec', $arrExif['EXIF']) ? $arrExif['EXIF']['LensSpec'] : '';
        $data['VibrationReduction'] = array_key_exists('VibrationReduction',
            $arrExif['MakerNotes']) ? $arrExif['MakerNotes']['VibrationReduction'] : '';
        foreach ($arrExif['Files'] as $file) {
            if (strtolower($file['FileType']) !== 'xmp') {
                $data['FileType'] = $file['FileType'];
                $data['FileSize'] = $file['FileSize'];
            }
        }
        $data['Lens'] = array_key_exists('Lens', $arrExif['EXIF']) ? $arrExif['EXIF']['Lens'] : '';
        $data['LensSpec'] = array_key_exists('LensID', $arrExif['Composite']) ? $arrExif['Composite']['LensID'] : '';
        $data['FocalLength'] = array_key_exists('FocalLength', $arrExif['EXIF']) ? $arrExif['EXIF']['FocalLength'] : '';
        $data['Make'] = array_key_exists('Make', $arrExif['EXIF']) ? $arrExif['EXIF']['Make'] : 'Nikon';
        $data['Model'] = array_key_exists('Model',
            $arrExif['EXIF']) ? $arrExif['EXIF']['Model'] : 'Nikon SUPER COOLSCAN 5000 ED';

        return $data;
    }

    /**
     * Creates the database structure.
     * @return void
     */
    private function createStructure()
    {
        $sql = "BEGIN;
        create table Countries
        (
        	Id INTEGER not null
        		primary key,
        	NameEn VARCHAR2,
        	NameDe VARCHAR2
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
        	ImgId INTEGER not null,
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
        
        alter table Exif
        	add constraint Exif_Images_Id_fk
        		foreign key (ImgId) references Images
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
        	Name VARCHAR2
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
        			COMMIT;";
        $this->db->exec($sql);
        print_r($this->db->errorInfo());

    }

    /**
     * @return string
     */
    private function GetWebRoot()
    {
        return $this->webroot;
    }
}