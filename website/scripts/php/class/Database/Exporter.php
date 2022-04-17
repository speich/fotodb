<?php

namespace PhotoDatabase\Database;

use JetBrains\PhpStorm\Pure;
use PDO;
use PDOStatement;
use PhotoDatabase\Thumbnail;
use RuntimeException;
use stdClass;


/**
 * Class Exporter
 * Exports the source database to the target database
 */
class Exporter extends Database
{
    /** @var string */
    private string $pathTargetDb;

    /** @var string */
    private string $pathTargetImages;

    /**
     * @param stdClass $config
     */
    #[Pure] public function __construct(stdClass $config)
    {
        parent::__construct($config);
        $this->pathTargetDb = $config->paths->targetDatabase;
        $this->pathTargetImages = $config->paths->targetImages;
    }

    /**
     * Query all new or modified records to process after export.
     * Returns all records which either have never been published previously or have been changed between the last publishing.
     * @return false|PDOStatement
     */
    private function getRecords(): bool|PDOStatement
    {
        // Select all records from source database which will be used to copy/delete images depending on their public status.
        // IMPORTANT: Do not limit sql to only public ones by using target database, because then you would miss deleting
        // thumbnails that changed state from public in previous export to private in this export
        // We purposely do not use WHERE IN, instead we update records in a loop one at a time after creation of
        // the thumbnail (since we don't use a transaction because journaling mode is off for speed)
        // Note: we need to query all private records te be able to remove them from the target database after copying
        $sql = "SELECT Id, ImgFolder, ImgFolder||'/'||ImgName Img, Public, ShowLoc, LastChange, DatePublished
            FROM Images
			WHERE LastChange > DatePublished OR DatePublished IS NULL";

        return $this->db->query($sql, PDO::FETCH_ASSOC);
    }

    /**
     * Set today's date to all new or modified records
     * @param PDO $db
     */
    private function setRecordsPublished(PDO $db): void
    {
        $time = time();
        $sql = 'UPDATE Images SET DatePublished = :time WHERE (LastChange > DatePublished OR DatePublished IS NULL)';
        $stmtDateSrc = $db->prepare($sql);
        $stmtDateSrc->bindParam(':time', $time);
        $stmtDateSrc->execute();
    }

    /**
     * Export database and images marked as public.
     * Just copies the whole database, then deletes unwanted records from it.
     * Creates a thumbnail from each exported image.
     * Warning: If the destination database file already exists, it will be overwritten.
     */
    public function publish(): void
    {
        set_time_limit(120);
        $this->copyImages();
        $targetDb = $this->copyDatabase();

        // Note: Since DB is just copied over, we have to delete all private records every time. Doing this only for changed/new records is not enough,
        // because previously deleted ones, get copied again.
        $sql = 'DELETE FROM Images WHERE Public = 0';
        $num = $targetDb->exec($sql);
        echo "deleted $num from table Images<br>";

        $sql = 'UPDATE Images SET ImgLat = NULL, ImgLng = NULL WHERE ShowLoc = 0';
        $targetDb->exec($sql);

        $sql = 'DELETE FROM Exif WHERE ImgId IN (SELECT Id FROM Images WHERE Public = 0)';
        $num = $targetDb->exec($sql);
        echo "deleted $num from table Exif<br>";

        $sql = 'UPDATE Exif SET GPSAltitude = NULL, GPSLongitude = NULL WHERE ImgId IN (SELECT Id FROM Images WHERE ShowLoc = 0)';
        $targetDb->exec($sql);

        $sql = 'DELETE FROM Images_fts WHERE ImgId IN (SELECT Id FROM Images WHERE Public = 0)';
        $num = $targetDb->exec($sql);
        echo "deleted $num from table Images_fts<br>";


        $sourceDb = $this->connect();
        $this->setRecordsPublished($sourceDb);
        $this->setRecordsPublished($targetDb);
    }

    /**
     * Copy database file to target database file.
     * Previous target database file will be overwritten.
     * @return PDO target database
     */
    private function copyDatabase(): PDO
    {
        $source = $this->getPath('Db');
        if (copy($source, $this->pathTargetDb)) {
            return new PDO('sqlite:'.$this->pathTargetDb);
        }

        $err = error_get_last();
        throw new RuntimeException($err['message'], $err['code']);
    }

    /**
     * Copy images of new or modified records.
     */
    private function copyImages(): void
    {
        $arrData = $this->getRecords();
        foreach ($arrData as $row) {
            $destImg = $this->pathTargetImages.'/'.$row['Img'];
            // copy image
            if ($row['Public'] === '1') {
                $dir = $this->pathTargetImages.'/'.$row['ImgFolder'];
                $this->createImgDirectories($dir);
                $srcImg = __DIR__.'/../../../../dbprivate/images/'.$row['Img'];
                $this->copyImage($srcImg, $destImg);
                echo "exported $destImg {$row['Id']}<br>";
            } // delete records and previously copied images that are no longer public
            else {
                $this->deleteImage($destImg);
                echo "deleted $destImg {$row['Id']}<br>";
            }
        }
    }

    /**
     * Delete an image from the filesystem.
     * @param string $img
     */
    public function deleteImage(string $img): void
    {
        if ((is_file($img) === true) && unlink(realpath($img)) === false) {
            throw new RuntimeException('could not delete image: '.$img);
        }
    }

    /**
     * Creates a directory and a thumbnail directory for the image if it does not exist.
     * @param string $dir directory path
     */
    private function createImgDirectories(string $dir): void
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
     * @param string $srcImg image path to the source
     * @param string $destImg image path to the destination
     */
    private function copyImage(string $srcImg, string $destImg): void
    {
        unlink($destImg);   // for some reason copy can not overwrite
        if (copy($srcImg, $destImg)) {
            $thumbnail = new Thumbnail();
            $destPath = str_replace('/images/', '/images/thumbs/', $destImg);
            unlink($destPath);
            $thumbnail->create($destImg, $destPath, $thumbnail->width);
        } else {
            throw new RuntimeException('Copying of image from'.$srcImg.' to '.$destImg.' failed.');
        }
    }
}