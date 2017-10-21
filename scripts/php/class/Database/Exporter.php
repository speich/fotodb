<?php
namespace PhotoDatabase\Database;

use PDO;


/**
 * Class Exporter
 */
class Exporter extends Database {

	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Export database and images marked as public.
	 * Creates a thumbnail from each exported image. The parameter mode allows either to recreate all records.
	 * Warning: If the destination database file already exists, it will be overwritten.
	 * @param string $destDb path to destination database
	 * @param string $destDirImg path to destination image folder
	 */
	public function publish($destDb, $destDirImg) {
		// TODO: use x-mixed-replace with json messaging for progress feedback
		// TODO: check permissions for exporting
		set_time_limit(0);

		// Copy database file
		// Instead of looping through all tables and records to find records that have changed or were added since
		// the last publishing, we just copy the whole db file and remove the private records.
		// We ignore deleting of keywords, etc. because they don't really matter and to keep it simple
		$srcDb = $this->GetPath('Db').$this->GetDbName();
		echo "source db: ".print_r(file_exists($srcDb), true)."<br>";
		echo "dest db: ".print_r(file_exists($destDb), true)."<br>";
		if (copy($srcDb, $destDb)) {
			echo 'db copy successful<br>';

			// For speed reasons we turn journaling off and increase cache size. As long as we import all records at once,
			// we do not need a rollback. We just start over in case of a crash. This is only possible for the destination
			// db, since the file might get corrupted.
			// default = 2000 pages, 1 page = 1kb;
			$destDb = new PDO('sqlite:'.$destDb);
			$sql = "PRAGMA journal_mode = OFF; PRAGMA cache_size = 10000;";
			$destDb->exec($sql);

			// Records with Public == 0 have always to be deleted independent of DatePublished, because they are copied with the database !
			$sql = "DELETE FROM Images WHERE Public = '0'";
			if ($destDb->exec($sql) === false) {
				exit('deleting records failed');
			}
		}
		else {
			exit('publishing failed');
		}

		// Select records which will be used to copy/delete images depending on their public status.
		// IMPORTANT: Do not limit sql to only public ones by using destDb, because then you would miss deleting
		// thumbnails that are no longer public.
		// We purposely do not use WHERE IN, instead we update records in a loop one at a time after creation of
		// the thumbnail (since we don't use a transaction because journaling mode is off for speed)
		$srcDb = $this->connect();
		$sql = "SELECT Id, ImgFolder, ImgFolder||'/'||ImgName Img, Public FROM Images
			WHERE (LastChange > DatePublished OR DatePublished IS NULL)";
		$stmt = $srcDb->prepare($sql);
		$stmt->execute();
		$arrData = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// source db
		$sql = 'UPDATE Images SET DatePublished = :Time WHERE Id = :ImgId';
		$stmt = $srcDb->prepare($sql);
		$stmt->bindParam(':Time', $Time);
		$stmt->bindParam(':ImgId', $imgId);
		// dest db
		$sql = 'UPDATE Images SET DatePublished = :Time WHERE Id = :ImgId';
		$stmt1 = $destDb->prepare($sql);
		$stmt1->bindParam(':Time', $Time);
		$stmt1->bindParam(':ImgId', $imgId);
		// remove location information where necessary
		// TODO: also remove location from exif
		$sql = "UPDATE Images SET ImgLat = NULL, ImgLng = NULL WHERE Id = :ImgId AND ShowLoc IS NULL OR ShowLoc = '0'";
		$stmt2 = $destDb->prepare($sql);
		$stmt2->bindParam(':ImgId', $imgId);

		foreach ($arrData as $row) {
			$srcImg = $this->GetDocRoot().ltrim($this->GetPath('Img').$row['Img'], '/');
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
				$succ = $this->createThumb($destImg, 180);
				// update rec where img is exported so in case of a resume we do not have to re-export it
				if ($succ) {
					$Time = time();
					$imgId = $row['Id'];
					$stmt->execute();
					$stmt1->execute();
					$stmt2->execute();
					echo "exported $destImg {$row['Id']}<br>";
				}
			}
			// delete images that are no longer public and remove location information if necessary
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
	 * Creates a thumbnail from provided image and stores it.
	 * @param string $imgFile
	 * @param integer $newWidth
	 * @return bool
	 */
	protected function createThumb($imgFile, $newWidth) {
		$img = imagecreatefromjpeg($imgFile);

		// calculate thumbnail height
		$width = imagesx($img);
		$height = imagesy($img);
		if ($width > $height) {
			$newHeight = floor($height * $newWidth / $width);
		}
		else {
			$newHeight = $newWidth;
			$newWidth = floor($width * $newHeight / $height);
		}
		// create new image and save it
		$tmpImg = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($tmpImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		$tmpImg = $this->unsharpMask($tmpImg, 60, 0.8, 0);
		$succ = imagejpeg($tmpImg, str_replace('images/', 'images/thumbs/', $imgFile), 100);
		imagedestroy($tmpImg);
		return $succ;
	}

	/**
	 * Image hast to be already created with imgcreatetruecolor.
	 * @param object $img
	 * @param object $amount
	 * @param object $radius
	 * @param object $threshold
	 * @return
	 */
	public function unsharpMask($img, $amount, $radius, $threshold) {
		/* Unsharp Mask for PHP - version 2.1.1
			Unsharp mask algorithm by Torstein Hønsi 2003-07.
			thoensi_at_netcom_dot_no. */

		// Attempt to calibrate the parameters to Photoshop:
		if ($amount > 500) {
			$amount = 500;
		}
		$amount = $amount * 0.016;
		if ($radius > 50) {
			$radius = 50;
		}
		$radius = $radius * 2;
		if ($threshold > 255) {
			$threshold = 255;
		}
		$radius = abs(round($radius)); // Only integers make sense.
		if ($radius == 0) {
			return false;
		}
		$w = imagesx($img);
		$h = imagesy($img);
		$imgCanvas = imagecreatetruecolor($w, $h);
		$imgBlur = imagecreatetruecolor($w, $h);

		/* Gaussian blur matrix:
				1  2  1
				2  4  2
				1  2  1
		*/
		$matrix = array(array(1, 2, 1), array(2, 4, 2), array(1, 2, 1));
		imagecopy($imgBlur, $img, 0, 0, 0, 0, $w, $h);
		imageconvolution($imgBlur, $matrix, 16, 0);
		if ($threshold > 0) {
			// Calculate the difference between the blurred pixels and the original
			// and set the pixels
			for ($x = 0; $x < $w - 1; $x++) { // each row
				for ($y = 0; $y < $h; $y++) { // each pixel
					$rgbOrig = ImageColorAt($img, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);

					$rgbBlur = ImageColorAt($imgBlur, $x, $y);
					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					// When the masked pixels differ less from the original
					// than the threshold specifies, they are set to their original value.
					$rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
					$gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
					$bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;

					if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
						$pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
						ImageSetPixel($img, $x, $y, $pixCol);
					}
				}
			}
		}
		else {
			for ($x = 0; $x < $w; $x++) { // each row
				for ($y = 0; $y < $h; $y++) { // each pixel
					$rgbOrig = ImageColorAt($img, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);

					$rgbBlur = ImageColorAt($imgBlur, $x, $y);
					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					$rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
					if ($rNew > 255) {
						$rNew = 255;
					}
					elseif ($rNew < 0) {
						$rNew = 0;
					}
					$gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
					if ($gNew > 255) {
						$gNew = 255;
					}
					elseif ($gNew < 0) {
						$gNew = 0;
					}
					$bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
					if ($bNew > 255) {
						$bNew = 255;
					}
					elseif ($bNew < 0) {
						$bNew = 0;
					}
					$rgbNew = ($rNew << 16) + ($gNew << 8) + $bNew;
					ImageSetPixel($img, $x, $y, $rgbNew);
				}
			}
		}
		imagedestroy($imgCanvas);
		imagedestroy($imgBlur);
		return $img;
	}
}
