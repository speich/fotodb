<?php
namespace PhotoDatabase\Database;

use Exiftool\Exceptions\ExifToolBatchException;
use Exiftool\ExifToolBatch;
use FilesystemIterator;
use PhotoDatabase\ExifService;
use PhotoDatabase\Iterator\FilterFilesXmp;
use PhotoDatabase\Iterator\FilterSyncXmp;
use PhotoDatabase\Iterator\PhotoDbDirectoryIterator;
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
        $files = new PhotoDbDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS, $imagesDb, $this->pathImagesOriginal);
        $filteredFiles = new FilterSyncXmp($files);
        $filteredFiles = new RecursiveIteratorIterator($filteredFiles);

        $exifService = ExifToolBatch::getInstance('/media/sf_Websites/fotodb/cgi-bin/Image-ExifTool-10.24/exiftool');
//        $exifService = new ExifService();
        foreach($filteredFiles as $fileinfo) {

            // TODO: getting exif info this way is expensive, use https://github.com/tsmgeek/ExifTool_PHP_Stayopen
            $imgId = $fileinfo->getImgId();
            $imgSrc = $fileinfo->getRealPath();
            $exifService->add($imgSrc);
            try {
                $arrExif = $exifService->fetchDecoded(true);
                $this->db->insertXmp($imgId, $arrExif[0]['XMP']);
                echo "syncing $imgSrc successful<br>";
            }
            catch (ExifToolBatchException $exception) {
                // TODO: write to log which files failed?
                echo "syncing $imgSrc failed<br>";
            }
        }

        /*if ($files) {

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

    /**
     * Query images to sync from database and create array where the keys are the image path without file extension
     * @param string $dir
     * @return array
     */
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