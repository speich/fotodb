<?php

namespace PhotoDatabase\Database;

use PDO;
use PhotoDatabase\Thumbnail;
use RuntimeException;


/**
 * Class Exporter
 * Exports the source database to the target database
 */
class Exporter extends Database
{

    /** @var string */
    private $pathTargetDb;

    /** @var string */
    private $pathTargetImages;

    /**
     * @param \stdClass $config
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->pathTargetDb = $config->paths->targetDatabase;
        $this->pathTargetImages = $config->paths->targetImages;
    }

    /**
     * Export database and images marked as public.
     * Creates a thumbnail from each exported image. The parameter mode allows either to recreate all records.
     * Warning: If the destination database file already exists, it will be overwritten.
     */
    public function publish(): void
    {
        set_time_limit(0);
        $time = time(); // variable is bound to sql query above

        $sourceDb = $this->connect();
        // get records to update/delete (before setting publishing date!)
        $arrData = $this->getRecords();
        // set date of published in source database before copying it
        $sql = 'UPDATE Images SET DatePublished = :Time WHERE (LastChange > DatePublished OR DatePublished IS NULL)';
        $stmtDateSrc = $sourceDb->prepare($sql);
        $stmtDateSrc->bindParam(':Time', $time);
        $stmtDateSrc->execute();
        $targetDb = $this->copy();

        // copy/delete records and images in target database
        // remove location information where necessary
        $sql = "UPDATE Images SET ImgLat = NULL, ImgLng = NULL WHERE Id = :ImgId";
        $stmtLocation = $targetDb->prepare($sql);
        $stmtLocation->bindParam(':ImgId', $imgId);
        // delete records no longer public
        $sql = 'DELETE FROM Images WHERE Id = :ImgId';
        $stmtImagesDel = $targetDb->prepare($sql);
        $stmtImagesDel->bindParam(':ImgId', $imgId);
        $sql = 'DELETE FROM Exif WHERE ImgId = :ImgId';
        $stmtExifDel = $targetDb->prepare($sql);
        $stmtExifDel->bindParam(':ImgId', $imgId);
        foreach ($arrData as $row) {
            $imgId = $row['Id']; // variable is bound to sql query above
            $destImg = $this->pathTargetImages.'/'.$row['Img'];
            // copy image
            if ($row['Public'] === '1') {
                $dir = $this->pathTargetImages.'/'.$row['ImgFolder'];
                $this->createImgDirectories($dir);
                $srcImg = __DIR__.'/../../../../dbprivate/images/'.$row['Img'];
                $this->copyImages($srcImg, $destImg);
                echo "exported $destImg {$row['Id']}<br>";
            }
            // delete records and previously copied images that are no longer public
            else {
                $stmtImagesDel->execute();
                $this->deleteImage($destImg);
                echo "deleted $destImg {$row['Id']}<br>";
            }
            if ($row['ShowLoc'] === null || $row['ShowLoc'] === '0') {
                $stmtLocation->execute();
                $stmtExifDel->execute();
            }
        }
    }

    /**
     * Copy database file to target database file.
     * Previous target database file will be overwritten.
     * @return PDO target database
     */
    public function copy(): PDO
    {
        $source = $this->getPath('Db');
        if (copy($source, $this->pathTargetDb)) {

            return new PDO('sqlite:'.$this->pathTargetDb);
        }

        $err = error_get_last();
        throw new RuntimeException($err['message'], $err['code']);
    }

    /**
     * Query records to process in export.
     * Returns all records which were either have been changed between the last publishing or that never have been published previously.
     * @return mixed
     */
    private function getRecords() {
        // Select all records from source database which will be used to copy/delete images depending on their public status.
        // IMPORTANT: Do not limit sql to only public ones by using destDb, because then you would miss deleting
        // thumbnails that changed state from public in previous export to private in this export
        // We purposely do not use WHERE IN, instead we update records in a loop one at a time after creation of
        // the thumbnail (since we don't use a transaction because journaling mode is off for speed)
        // TODO: queries to many records, over and over again, e.g. where Public = 0 or DatePublished = NULL
        $sql = "SELECT Id, ImgFolder, ImgFolder||'/'||ImgName Img, Public, ShowLoc FROM Images
			WHERE (LastChange > DatePublished OR DatePublished IS NULL)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete an image from the filesystem.
     * @param $img
     */
    public function deleteImage($img): void
    {
        if ((is_file($img) === true) && unlink(realpath($img)) === false) {
            throw new RuntimeException('could not delete image: '.$img);
        }
    }

    /**
     * Creates a directory and a thumbnail directory for the image if it does not exist.
     * @param string $dir directory path
     */
    private function createImgDirectories($dir): void
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException('creating directory '.$dir.' failed.');
            }
            $dirThumbnails = str_replace('/images/', '/images/thumbs/', $dir);
            if (!mkdir($dirThumbnails, 0777, true) && !is_dir($dirThumbnails)) {
                throw new RuntimeException('Creating thumbnails directory '.$dir.' failed.');
            }
        }
    }

    /**
     * Copy image and create thumbnail.
     * @param string $srcImg image path
     * @param string $destImg image path
     */
    private function copyImages($srcImg, $destImg): void
    {
        if (copy($srcImg, $destImg)) {
            $thumbnail = new Thumbnail();
            $destPath = str_replace('/images/', '/images/thumbs/', $destImg);
            $thumbnail->create($destImg, $destPath, $thumbnail->width);
        } else {
            throw new RuntimeException('Copying of image from'.$srcImg.' to '.$destImg.' failed.');
        }
    }

    public function createSearch()
    {

    }

}
