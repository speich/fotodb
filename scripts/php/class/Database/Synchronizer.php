<?php
namespace PhotoDatabase\Database;



/**
 * Class Synchronizer
 * Helper class to synchronize image file data with the data in the photo database
 */
class Synchronizer
{
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function syncXmp($imageFolder)
    {
        $imgFolderOriginal = $this->db->GetPath('ImgOriginal').'/'.$imageFolder;

        $files = scandir($imageFolder);
        if ($files) {

            $arrExif = $this->db->getExif($imgSrc);
            //$exifData = $this->db->mapExif($arrExif);
            //$this->db->insertXmp($imgId, $arrExif);
            //$this->db->BeginTransaction();
        }
        else {
            trigger_error()
        }

        return true;
    }

    public function syncExif($imgFolder) {
        return true;
    }
}