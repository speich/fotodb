<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 9/3/17
 * Time: 3:35 PM
 */

namespace PhotoDb;

use FotoDb;


/**
 * Class DbSynchronizer
 * Helper class to synchronize image file data with the data in the photo database
 */
class DbSynchronizer
{
    public function __construct(FotoDb $db)
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
            $this->db->insertXmp($imgId, $arrExif);
            $this->db->BeginTransaction();
        }
    }

}