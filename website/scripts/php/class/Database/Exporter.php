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
        $sourceDb = $this->connect();
        $targetDb = $this->copyAndClean();
        $arrData = $this->getRecords();

        // source db
        $sql = 'UPDATE Images SET DatePublished = :Time WHERE Id = :ImgId';
        $stmtDateSrc = $sourceDb->prepare($sql);
        $stmtDateSrc->bindParam(':Time', $time);
        $stmtDateSrc->bindParam(':ImgId', $imgId);
        // dest db
        $sql = 'UPDATE Images SET DatePublished = :Time WHERE Id = :ImgId';
        $stmtDateDest = $targetDb->prepare($sql);
        $stmtDateDest->bindParam(':Time', $time);
        $stmtDateDest->bindParam(':ImgId', $imgId);
        // remove location information where necessary
        // TODO: also remove location from exif
        $sql = "UPDATE Images SET ImgLat = NULL, ImgLng = NULL WHERE Id = :ImgId AND ShowLoc IS NULL OR ShowLoc = '0'";
        $stmtLocation = $targetDb->prepare($sql);
        $stmtLocation->bindParam(':ImgId', $imgId);
        foreach ($arrData as $row) {
            $destImg = $this->pathTargetImages.'/'.$row['Img'];
            // copy image
            if ($row['Public'] === '1') {
                $dir = $this->pathTargetImages.'/'.$row['ImgFolder'];
                $this->createImgDirectories($dir);
                $srcImg = __DIR__.'/../../../../dbprivate/images/'.$row['Img'];
                $this->copyImages($srcImg, $destImg);
                // update DatePublished of exported image so it will not be re-exported until LastChange is updated
                $time = time(); // variable is bound to sql query above
                $imgId = $row['Id']; // variable is bound to sql query above
                $stmtDateSrc->execute();
                $stmtDateDest->execute();
                echo "exported $destImg {$row['Id']}<br>";
            }
            // delete previously copied images that are no longer public
            else {
                $this->deleteImage($destImg);
                echo "(already) deleted $destImg {$row['Id']}<br>";
            }
            $stmtLocation->execute();
        }
    }

    /**
     * Copy database file to target database file.
     * Instead of looping through all tables and records to find records that have changed or were added since
     * the last publishing. We just copy the whole db file and then remove the private records of the images table before.
     * We ignore deleting of keywords, etc. because they don't really matter and to keep it simple.
     * Previous target database file will be overwritten.
     * @return PDO target database
     */
    public function copyAndClean(): PDO
    {
        $source = $this->getPath('Db');
        if (copy($source, $this->pathTargetDb)) {
            // For speed reasons we turn journaling off and increase cache size. As long as we import all records at once,
            // we do not need a rollback. We just start over in case of a crash. This is only possible for the destination
            // db, since the file might get corrupted.
            // default = 2000 pages, 1 page = 1kb;
            $targetDb = new PDO('sqlite:'.$this->pathTargetDb);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = 'PRAGMA journal_mode = OFF; PRAGMA cache_size = 10000;';
            $targetDb->exec($sql);

            // Records with Public == 0 have always to be deleted independent of DatePublished, because they are copied with the database !
            $sql = "DELETE FROM Images WHERE Public = '0'";
            $targetDb->exec($sql);

            return $targetDb;
        }

        $err = error_get_last();
        throw new RuntimeException($err['message'], $err['code']);
    }

    /**
     * Query records to process in export.
     * @return mixed
     */
    private function getRecords() {
        // Select all records from source database which will be used to copy/delete images depending on their public status.
        // IMPORTANT: Do not limit sql to only public ones by using destDb, because then you would miss deleting
        // thumbnails that changed state from public in previous export to private in this export
        // We purposely do not use WHERE IN, instead we update records in a loop one at a time after creation of
        // the thumbnail (since we don't use a transaction because journaling mode is off for speed)
        // TODO: queries to many records, over and over again, e.g. where Public = 0 or DatePublished = NULL
        $sql = "SELECT Id, ImgFolder, ImgFolder||'/'||ImgName Img, Public FROM Images
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
            $thumbnail->create($destImg, $destPath, 180);
        } else {
            throw new RuntimeException('Copying of image from'.$srcImg.' to '.$destImg.' failed.');
        }
    }

    public function createSearch()
    {

    }

}
