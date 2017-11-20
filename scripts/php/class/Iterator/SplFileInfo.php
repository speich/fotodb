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

    private $syncDate;

    /**
     * Sets the image id attribute
     * @param $id
     */
    public function setImgId($id) {
        $this->imgId = (int) $id;
    }

    /**
     * Returns the sync date
     * @return null||DateTime
     */
    public function getSyncDate()
    {
        return $this->syncDate === null ? null : new DateTime($this->syncDate);
    }

    /**
     * Sets the sync data attribute
     * @param mixed $syncDate
     */
    public function setSyncDate($syncDate)
    {
        $this->syncDate = $syncDate;
    }

    /**
     * Returns the image id
     * @return mixed | int
     */
    public function getImgId() {
        return $this->imgId;
    }
}