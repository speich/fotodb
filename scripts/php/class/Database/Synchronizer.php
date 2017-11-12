<?php
namespace PhotoDatabase\Database;

use FilesystemIterator;
use PhotoDatabase\FilterFilesXmp;
use PhotoDatabase\FilterSyncXmp;
use PhotoDatabase\PhotoDbDirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;


/**
 * Class Synchronizer
 * Helper class to synchronize image file data with the data in the photo database
 */
class Synchronizer
{
    /** @var Database */
    private $db;

    /** @var string absolute path to folder with original images */
    private $pathImagesOriginal;

    /**
     * Synchronizer constructor.
     * @param Database $db
     * @param stdClass $config
     */
    public function __construct(Database $db, $config)
    {
        $this->pathImagesOriginal = $config->paths->imagesOriginal;
        $this->db = $db;
    }

    /**
     * Update XMP data in database from files in folder.
     * @param string $imageFolder folder with XMP data to update
     * @return string json
     */
    public function updateXmp($dir)
    {

        // get images to sync from database and create array where keys are the image path without file extension
        $imagesDb = $this->getImagesDb($dir);


        // get and filter images to sync from filesystem
        $dir = $this->pathImagesOriginal.'/'.$dir;
        $files = new PhotoDbDirectoryIterator($dir, $imagesDb, $this->pathImagesOriginal, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS);
        $filteredFiles = new FilterFilesXmp($files);
        $filteredFiles = new FilterSyncXmp($filteredFiles, $imagesDb, $this->pathImagesOriginal);
        $filteredFiles = new RecursiveIteratorIterator($filteredFiles);

        foreach($filteredFiles as $fileinfo) {
            // INSERT OR REPLACE INTO Xmp
            // Database::insertXmp()
            var_dump($fileinfo);
            //$this->db->insertXmp($imgId, $arrExif);
        }

        /*if ($files) {

            //$arrExif = $this->db->getExif($imgSrc);
            //$exifData = $this->db->mapExif($arrExif);

        } else {
            //trigger_error();
        }*/

        return true;
    }

    /**
     * Update EXIF data in database from files in folder
     * @param string $imageFolder folder with XMP data to update
     * @return bool
     */
    public function updateExif($imgFolder)
    {
        return true;
    }

    private function getImagesDb($dir)
    {
        $images = [];
        $dirParam = $dir.'%';
        $sql = "SELECT i.Id, i.ImgFolder||'/'||i.ImgName Img, x.SyncDate FROM Images i
            LEFT JOIN Xmp x ON i.id = x.ImgId";
        $sql .= $dir === '' ? '' : ' WHERE i.ImgFolder LIKE :Folder';
        $stmt = $this->db->db->prepare($sql);
        if ($dir !== '') {
            $stmt->bindParam(':Folder', $dirParam);
        }
        $stmt->execute();
        foreach ($stmt as $row) {
            // create array lookup with image path as id
            $key = explode('.', $row['Img']);
            $images[$key[0]] = $row;
        }

        return $images;
    }
}