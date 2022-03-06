<?php

namespace PhotoDatabase\Iterator;

use DateTime;
use Exception;
use SplFileInfo;


/**
 * Adds image database specific attributes and methods to the SplFileInfo class
 */
class FileInfoImage extends SplFileInfo
{
    private mixed $imgId;

    /** @var null|string sync date */
    private ?string $syncDateXmp;

    /** @var null|string sync date */
    private ?string $syncDateExif;

    /**
     * File extensions to check for raw against.
     * @var string[]
     */
    private array $validRawExtensions = ['nef', 'arw', 'dng'];

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
     * @throws Exception
     */
    public function getSyncDateXmp(): ?DateTime
    {
        // note: DateTime(null) would return current date, also syncDateXmp is a string and has to be converted to
        return $this->syncDateXmp === null ? null : new DateTime($this->syncDateXmp);
    }

    /**
     * Returns the exif sync date as a DateTime object
     * @return null|DateTime
     * @throws Exception
     */
    public function getSyncDateExif(): ?DateTime
    {
        return $this->syncDateExif === null ? null : new DateTime($this->syncDateExif);
    }

    /**
     * Sets the exif sync data attribute
     * @param string $syncDate SQLite ISO-8601 date string
     */
    public function setSyncDateExif(string $syncDate): void
    {
        $this->syncDateExif = $syncDate;
    }

    /**
     * Sets the xmp sync data attribute
     * @param string $syncDate SQLite ISO-8601 date string
     */
    public function setSyncDateXmp(string $syncDate): void
    {
        $this->syncDateXmp = $syncDate;
    }

    /**
     * Returns the image id
     * @return string|int
     */
    public function getImgId(): int|string
    {
        return $this->imgId;
    }

    /**
     * Returns the extension of the corresponding XMP file of the image if any.
     * Note: comparison of extension is case-insensitive
     * @return null|string full path to xmp file of image
     */
    public function getRealPathXmp(): ?string
    {
        $pathNoExt = $this->getRealPathNoExtension();

        return $this->getRealPathCI($pathNoExt, 'xmp');
    }

    /**
     * Returns the extension of the corresponding raw file of the image if any.
     * Note: comparison of extension is case-insensitive
     * @return null|string full path to raw image
     */
    public function getRealPathRaw(): ?string
    {
        $path = null;
        $pathNoExt = $this->getRealPathNoExtension();
        foreach ($this->validRawExtensions as $ext) {
            $path = $this->getRealPathCI($pathNoExt, $ext);
            if ($path !== null) {
                break;
            }
        }

        return $path;
    }

    /**
     * Check if file path exists with case-insensitive extension.
     * @param string $path real path without extensions
     * @param string $ext file extension to check with
     * @return string|null
     */
    private function getRealPathCI(string $path, string $ext): ?string
    {
        $currPath = $path.'.'.$ext;
        if (file_exists($currPath)) {
            $realpath = $currPath;
        } else {
            $currPath = $path.'.'.strtoupper($ext);
            $realpath = file_exists($currPath) ? $currPath : null;
        }

        return $realpath;
    }

    /**
     * Return the real path of this image file without the extension.
     * @return string
     */
    private function getRealPathNoExtension(): string
    {
        return $this->getPath().'/'.$this->getBasename('.'.$this->getExtension());
    }

}