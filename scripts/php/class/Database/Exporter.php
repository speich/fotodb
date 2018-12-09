<?php
namespace PhotoDatabase\Database;

use PDO;
use PhotoDatabase\Thumbnail;


/**
 * Class Exporter
 */
class Exporter extends Database {

	/**
	 *
	 */
	public function __construct($config) {
		parent::__construct($config);
	}

	/**
	 * Export database and images marked as public.
	 * Creates a thumbnail from each exported image. The parameter mode allows either to recreate all records.
	 * Warning: If the destination database file already exists, it will be overwritten.
	 * @param string $target path to destination database
	 * @param string $destDirImg path to destination image root folder
	 */
	public function publish($target, $destDirImg) {
		// TODO: use x-mixed-replace with json messaging for progress feedback
		// TODO: check permissions for exporting
		set_time_limit(0);
        $thumbnail = new Thumbnail();

        $source = $this->GetPath('Db').$this->GetDbName();
		$sourceDb = $this->connect();
        $targetDb = $this->copyAndClean($source, $target);

		// Select all records which will be used to copy/delete images depending on their public status.
		// IMPORTANT: Do not limit sql to only public ones by using destDb, because then you would miss deleting
		// thumbnails that changed state from public in previous export to private in this export
		// We purposely do not use WHERE IN, instead we update records in a loop one at a time after creation of
		// the thumbnail (since we don't use a transaction because journaling mode is off for speed)
		$sql = "SELECT Id, ImgFolder, ImgFolder||'/'||ImgName Img, Public FROM Images
			WHERE (LastChange > DatePublished OR DatePublished IS NULL)";
		$stmt = $sourceDb->prepare($sql);
		$stmt->execute();
		$arrData = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// source db
		$sql = 'UPDATE Images SET DatePublished = :Time WHERE Id = :ImgId';
		$stmt = $sourceDb->prepare($sql);
		$stmt->bindParam(':Time', $time);
		$stmt->bindParam(':ImgId', $imgId);
		// dest db
		$sql = 'UPDATE Images SET DatePublished = :Time WHERE Id = :ImgId';
		$stmt1 = $targetDb->prepare($sql);
		$stmt1->bindParam(':Time', $time);
		$stmt1->bindParam(':ImgId', $imgId);
		// remove location information where necessary
		// TODO: also remove location from exif
		$sql = "UPDATE Images SET ImgLat = NULL, ImgLng = NULL WHERE Id = :ImgId AND ShowLoc IS NULL OR ShowLoc = '0'";
		$stmt2 = $targetDb->prepare($sql);
		$stmt2->bindParam(':ImgId', $imgId);

		foreach ($arrData as $row) {
			$srcImg = __DIR__.'/../../../../'.$this->GetPath('Img').'/'.$row['Img'];
			$destImg = $destDirImg.'/'.$row['Img'];
			// copy image
			if ($row['Public'] === '1') {
				$dir = $destDirImg.'/'.$row['ImgFolder'];
				if (!is_dir($dir)) {
					$succ = mkdir($dir, 0777, true);
					if (!$succ) {
						exit('making of dir '.$dir.' failed.');
					}
					$succ = mkdir(str_replace('images/', 'images/thumbs/', $dir), 0777, true);
					if (!$succ) {
						exit('making of dir '.$dir.' failed.');
					}
				}
				$succ = copy($srcImg, $destImg);
				if (!$succ) {
					exit('copying of img '.$srcImg.' failed.');
				}
				// create thumbnail
                $destPath = str_replace('images/', 'images/thumbs/', $destImg);
				$succ = $thumbnail->create($destImg, $destPath,180);
				// update rec where img is exported so in case of a resume we do not have to re-export it
				if ($succ) {
					$time = time(); // bound to sql query above
					$imgId = $row['Id']; // bound to sql query above
					$stmt->execute();
					$stmt1->execute();
					$stmt2->execute();
					echo "exported $destImg {$row['Id']}<br>";
				}
			}
			// delete images that are no longer public
			else {
				if (is_file($destImg)) {
					unlink($destImg);
					echo "deleted $destImg<br>";
				}
				$stmt2->execute();
			}
		}
	}

    /**
     * Copy database file to target database file.
     * Instead of looping through all tables and records to find records that have changed or were added since
     * the last publishing. We just copy the whole db file and then remove the private records of the images table before.
     * We ignore deleting of keywords, etc. because they don't really matter and to keep it simple
     * Previous target database file will be overwritten.
     * @param string $source path to source database file
     * @param string $target path to target database file
     * @return PDO target database
     */
    public function copyAndClean($source, $target)
    {

        echo "source db: ".print_r(file_exists($source), true)."<br>";
        echo "dest db: ".print_r(file_exists($target), true)."<br>";
        if (copy($source, $target)) {
            echo 'db copy successful<br>';

            // For speed reasons we turn journaling off and increase cache size. As long as we import all records at once,
            // we do not need a rollback. We just start over in case of a crash. This is only possible for the destination
            // db, since the file might get corrupted.
            // default = 2000 pages, 1 page = 1kb;
            $targetDb = new PDO('sqlite:'.$target);
            $sql = "PRAGMA journal_mode = OFF; PRAGMA cache_size = 10000;";
            $targetDb->exec($sql);

            // Records with Public == 0 have always to be deleted independent of DatePublished, because they are copied with the database !
            $sql = "DELETE FROM Images WHERE Public = '0'";
            if ($targetDb->exec($sql) === false) {
                exit('deleting records failed');
            }
        } else {
            exit('publishing failed');
        }

        return $targetDb;
    }

    public function createSearch() {

    }

}
