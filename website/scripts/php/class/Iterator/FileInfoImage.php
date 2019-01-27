<?php

namespace PhotoDatabase\Iterator;

use DateTime;


/**
 * Class SplFileInfo
 * Adds additional attributes from the image database to the file
 * @package PhotoDatabase\Explorer
 */
class FileInfoImage extends \SplFileInfo
{
    private $imgId;

    /** @var null|string sync date */
    private $syncDateXmp;

    /** @var null|string sync date */
    private $syncDateExif;

    private $validRawExtensions = ['nef', 'arw'];

    /**
     * Sets the image id attribute
     * @param $id
     */
    public function setImgId($id): void
    {
        $this->imgId = (int)$id;
    }

    /**
     * Returns the xmp sync date as a DateTime object
     * @return null|DateTime
     */
    public function getSyncDateXmp(): ?DateTime
    {
        // note: DateTime(null) would return current date, also syncDateXmp is a string and has to be converted to
        return $this->syncDateXmp === null ? null : new DateTime($this->syncDateXmp);
    }

    /**
     * Returns the exif sync date as a DateTime object
     * @return null|DateTime
     */
    public function getSyncDateExif(): ?DateTime
    {
        return $this->syncDateExif === null ? null : new DateTime($this->syncDateExif);
    }

    /**
     * Sets the exif sync data attribute
     * @param string $syncDate SQLite ISO-8601 date string
     */
    public function setSyncDateExif($syncDate): void
    {
        $this->syncDateExif = $syncDate;
    }

    /**
     * Sets the xmp sync data attribute
     * @param string $syncDate SQLite ISO-8601 date string
     */
    public function setSyncDateXmp($syncDate): void
    {
        $this->syncDateXmp = $syncDate;
    }

    /**
     * Returns the image id
     * @return mixed|int
     */
    public function getImgId()
    {
        return $this->imgId;
    }

    /**
     * Returns the extension of the corresponding XMP file of the image if any.
     * Note: comparison of extension is case insensitive
     * @return null|string full path to xmp file of image
     */
    public function getRealPathXmp(): ?string
    {
        $pathNoExt = $this->getPath().'/'.$this->getBasename('.'.$this->getExtension());
        $path = $pathNoExt.'.xmp';
        if (file_exists($path)) {
            return $path;
        }
        $path = $pathNoExt.'.XMP';
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Returns the extension of the corresponding raw file of the image if any.
     * Note: comparison of extension is case insensitive
     * @return null|string full path to raw image
     */
    public function getRealPathRaw(): ?string
    {
        $pathNoExt = $this->getPath().'/'.$this->getBasename('.'.$this->getExtension());
        foreach ($this->validRawExtensions as $ext) {
            $path = $pathNoExt.'.'.$ext;
            if (file_exists($path)) {
                return $path;
            }
            $path = $pathNoExt.'.'.strtoupper($ext);
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

}