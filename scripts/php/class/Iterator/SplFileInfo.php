<?php

namespace PhotoDatabase\Iterator;

use DateTime;


/**
 * Class SplFileInfo
 * Adds additional attributes from the image database to the file
 * @package PhotoDatabase\Explorer
 */
class SplFileInfo extends \SplFileInfo
{
    private $imgId;

    private $syncDateXmp;

    private $syncDateExif;

    /**
     * Sets the image id attribute
     * @param $id
     */
    public function setImgId($id)
    {
        $this->imgId = (int)$id;
    }

    /**
     * Returns the xmp sync date
     * @return null||DateTime
     */
    public function getSyncDateXmp()
    {
        return $this->syncDateXmp === null ? null : new DateTime($this->syncDateXmp);
    }

    /**
     * Returns the exif sync date
     * @return null||DateTime
     */
    public function getSyncDateExif()
    {
        return $this->syncDateExif === null ? null : new DateTime($this->syncDateExif);
    }

    /**
     * Sets the exif sync data attribute
     * @param mixed $syncDate
     */
    public function setSyncDateExif($syncDate)
    {
        $this->syncDateExif = $syncDate;
    }

    /**
     * Sets the xmp sync data attribute
     * @param mixed $syncDate
     */
    public function setSyncDateXmp($syncDate)
    {
        $this->syncDateXmp = $syncDate;
    }

    /**
     * Returns the image id
     * @return mixed | int
     */
    public function getImgId()
    {
        return $this->imgId;
    }
}