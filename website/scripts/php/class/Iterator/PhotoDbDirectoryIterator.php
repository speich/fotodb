<?php

namespace PhotoDatabase\Iterator;

use JetBrains\PhpStorm\Pure;
use RecursiveDirectoryIterator;


/**
 * Class PhotoDbDirectoryIterator
 * Adds additional information from database to each file
 * @package PhotoDatabase
 */
class PhotoDbDirectoryIterator extends RecursiveDirectoryIterator
{
    /** @var array */
    private array $dbRecords;

    /** @var string */
    private string $pathPrefix;

    /**
     * PhotoDbDirectoryIterator constructor.
     * @param string $path
     * @param array $dbRecords
     * @param string $pathPrefix
     * @param $flags
     */
    #[Pure] public function __construct(string $path, int $flags, array $dbRecords, string $pathPrefix)
    {
        parent::__construct($path, $flags);
        $this->dbRecords = $dbRecords;
        $this->pathPrefix = $pathPrefix;
    }

    /**
     * Return image record
     * @return null|array
     */
    private function getRecord(): ?array
    {
        $pathNoPrefix = $this->getNonPrefixedPath();
        // note: file can have more than one extension
        $arr = explode('.', $pathNoPrefix);

        return $this->dbRecords[$arr[0]];
    }

    /**
     * Returns the path with the prefix removed.
     * @return string
     */
    public function getNonPrefixedPath(): string
    {
        $pathLength = mb_strlen($this->pathPrefix) + 1;   // will remove slash

        return mb_substr($this->getRealPath(), $pathLength);
    }

    /**
     * The current file
     * @link https://php.net/manual/en/filesystemiterator.current.php
     * @return FileInfoImage $this
     * See the FilesystemIterator constants.
     * @since 5.3.0
     */
    public function current(): FileInfoImage
    {
        /** @var FileInfoImage $obj */
        $obj = parent::current();
        $record = $this->getRecord();
        if ($record) {
            $obj->setImgId($record['Id']);
            $obj->setSyncDateExif($record['SyncDateExif']);
            $obj->setSyncDateXmp($record['SyncDateXmp']);
        }

        return $obj;
    }

    /**
     * Returns an iterator for the current entry if it is a directory
     * @link http://php.net/manual/en/recursivedirectoryiterator.getchildren.php
     * @return PhotoDbDirectoryIterator An iterator for the current entry, if it is a directory.
     * @since 5.1.0
     */
    public function getChildren(): PhotoDbDirectoryIterator
    {
        return new self($this->getRealPath(), $this->getFlags(), $this->dbRecords, $this->pathPrefix);
    }

}