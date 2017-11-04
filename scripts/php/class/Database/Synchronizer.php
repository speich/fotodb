<?php
namespace PhotoDatabase\Database;

use stdClass;


/**
 * Class Synchronizer
 * Helper class to synchronize image file data with the data in the photo database
 */
class Synchronizer
{
    /** @var Database  */
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
    public function updateXmp($imageFolder)
    {
        $files = scandir($this->pathImagesOriginal.'/'.$imageFolder, null);
        $files = array_diff($files, ['.', '..']);
        if ($files) {

            $arrExif = $this->db->getExif($imgSrc);
            //$exifData = $this->db->mapExif($arrExif);
            //$this->db->insertXmp($imgId, $arrExif);
            //$this->db->BeginTransaction();
        }
        else {
            //trigger_error();
        }

        return true;
    }

    /**
     * Update EXIF data in database from files in folder
     * @param string $imageFolder folder with XMP data to update
     * @return bool
     */
    public function updateExif($imgFolder) {
        return true;
    }
}