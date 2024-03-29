<?php
namespace PhotoDatabase\Database;

use Exiftool\Exceptions\ExifToolBatchException;
use Exiftool\ExifToolBatch;
use FilesystemIterator;
use PDO;
use PhotoDatabase\Iterator\FilterSync;
use PhotoDatabase\Iterator\PhotoDbDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;
use PhotoDatabase\Iterator\FileInfoImage;


/**
 * Class Synchronizer
 * Helper class to synchronize image file data with the data in the photo database
 */
class Synchronizer
{
    /** @var Database */
    private Database $db;

    /** @var string absolute path to folder with original images */
    private string $pathImagesOriginal;
    private string $pathExifTool;

    /**
     * Synchronizer constructor.
     * @param Database $db
     * @param stdClass $config
     */
    public function __construct(Database $db, stdClass $config)
    {
        $this->pathExifTool = $config->paths->exifTool;
        $this->pathImagesOriginal = $config->paths->imagesOriginal;
        $this->db = $db;
    }

    /**
     * Update XMP data in database from files in folder.
     * @param string $dir folder with XMP data to update
     * @return string json
     */
    public function updateXmp(string $dir)
    {
        // get images to sync from database and create array where keys are the image path without file extension
        $imagesDb = $this->getImagesDb($dir);

        // get and filter images to sync from filesystem
        $dir = $this->pathImagesOriginal.'/'.$dir;
        $iterator = new PhotoDbDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS, $imagesDb, $this->pathImagesOriginal);
        $iterator->setInfoClass(FileInfoImage::class);
        $filteredFiles = new FilterFilesXmp($iterator);
        $filteredFiles = new RecursiveIteratorIterator($filteredFiles);

        $exifService = ExifToolBatch::getInstance($this->pathExifTool.'/exiftool');
        foreach($filteredFiles as $fileinfo) {
            $imgId = $fileinfo->getImgId();
            $imgSrc = $fileinfo->getRealPath();
            $exifService?->add($imgSrc);
            try {
                $arrExif = $exifService?->fetchDecoded(true);
                //$this->db->insertXmp($imgId, $arrExif[0]['XMP']);
                echo "syncing $imgSrc successful<br>";
            }
            catch (ExifToolBatchException $exception) {
                // TODO: write to log which files failed?
                echo "syncing $imgSrc failed<br>";
            }
        }

        return true;
    }

    /**
     * Update EXIF data in database from files in folder.
     * @param string $dir folder with XMP data to update
     * @return string json
     */
    public function updateExif($dir)
    {
        // get images to sync from database and create array where keys are the image path without file extension
        $imagesDb = $this->getImagesDb($dir);

        // get and filter images to sync from filesystem
        $dir = $this->pathImagesOriginal.'/'.$dir;
        $files = new PhotoDbDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS, $imagesDb, $this->pathImagesOriginal);
        $files->setInfoClass(FileInfoImage::class);
        $filteredFiles = new FilterSyncExif($files);
        $filteredFiles = new RecursiveIteratorIterator($filteredFiles);

        $exifService = ExifToolBatch::getInstance($this->pathExifTool.'/exiftool');
        foreach($filteredFiles as $fileinfo) {
            $imgId = $fileinfo->getImgId();
            $imgSrc = $fileinfo->getRealPath();
            $exifService?->add($imgSrc);
            try {
                $arrExif = $exifService?->fetchDecoded(true);
                $this->db->upsertExif($imgId, $arrExif[0]['EXIF']);
                echo "syncing $imgSrc successful<br>";
            }
            catch (ExifToolBatchException $exception) {
                // TODO: write to log which files failed?
                echo "syncing $imgSrc failed<br>";
            }
        }

        return true;
    }

    /**
     * Query images to sync from database and create array where the keys are the image path without file extension
     * @param string $dir
     * @return array
     */
    private function getImagesDb($dir)
    {
        $images = [];
        $dirParam = $dir.'%';
        $sql = "SELECT i.Id, i.ImgFolder||'/'||i.ImgName Img, 
            x.SyncDate SyncDateXmp, e.SyncDate SyncDateExif 
            FROM Images i
            LEFT JOIN Xmp x ON i.id = x.ImgId
            LEFT JOIN Exif e ON i.id = e.ImgId";
        $sql .= $dir === '' ? '' : ' WHERE i.ImgFolder LIKE :Folder';
        $stmt = $this->db->db->prepare($sql);
        if ($dir !== '') {
            $stmt->bindParam(':Folder', $dirParam);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        while ($row) {
            // create array lookup with image path as id
            $key = explode('.', $row['Img']);
            $images[$key[0]] = $row;
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $images;
    }
}