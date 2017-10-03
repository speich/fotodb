<?php
namespace PhotoDatabase;

use PDO;
use PhotoDatabase\Database\Database;


/**
 * File Explorer Class
 *
 */
class FileExplorer {
	private $TopDir = '';
    /**
     * @var Database
     */
    private $db; // top level directory. Do not allow going outside it unless explicitly set.

    /**
     * Initializes the Explorer class.
     *
     * @constructor
     * @param Database $db
     */
	public function __construct(Database $db) {
        $this->db = $db;
    }

	/**
	 * TopDir is the topmost website directory a user can browse to. For security reasons
	 * a user can not go outside the webroot.
	 * @param string $TopDir directory path
	 */
	public function SetTopDir($Dir) {
		// TODO: check if absolute path from webroot.
		$this->TopDir = $Dir;
	}

	/**
	 * Return absolute folder path where images are stored.
	 * @return string
	 */
	public function GetTopDir() {
		return $this->TopDir;
	}

	/**
	 * Reads a directory and returns an 2-dim array with information about the containing image files.
	 *
	 * For reading web directories all paths have to be provided as absolute paths, because
	 * this method can not know from where it has been called ($_SERVER['HTTP_REFERER'] is not
	 * reliable). $Path is expected to be an absolute path,
	 * but without webroot.
	 * User is limited to folder TopDir in webroot.
	 *   [0] => Array (
	 *      [Current] => dbprivate/images/us002/
	 *   )
	 *   [1] => Array (
	 *      [Path] => /dbprivate/images
	 *      [Type] => Dir
	 *      [Name] => ..
	 *      [Size] => 0.00kb
	 *   )
	 *   [2] => Array (
	 *      [Path] => /dbprivate/images/us002/
	 *      [Type] => File
	 *      [Name] => us002-092.jpg
	 *      [Size] => 69.01kb
	 *      [PathDbImg] => us002/
	 *      [DbImg] => us002/us002-092.jpg
	 *   )
	 * ...
	 * @param string $path
	 * @return array
	 */
	public function ReadDirWeb($path) {
		// TODO: make this safe for live web (is the use of strlen ok?)
		// argument $Path with trailing slash but no leading slash
		$ParentDir = substr($path, 0, strrpos(rtrim($path, '/'), '/') + 1);
		$filePath = $_SERVER['DOCUMENT_ROOT'].$path;
		
		$arrFile = [];
		$files = scandir($filePath, SCANDIR_SORT_ASCENDING);
		if ($files) {
			$arrFile[0]['Current'] = $path;
			foreach ($files as $file) {
				if ($file == ".") {
					continue;
				} // ignore self link
				if ($file == '..') { // parent directory link, do not display if already in TopDir
					if ($path == $this->TopDir) {
						continue;
					}
					$arrFile[$file]['Path'] = $ParentDir;
				}
				else {
					$arrFile[$file]['Path'] = $path;
				}
				$arrFile[$file]['Type'] = ucfirst(filetype($filePath.$file));
				$arrFile[$file]['Name'] = $file;
				$arrFile[$file]['Size'] = number_format(filesize($filePath.$file) / 1000, 2, ".", "'")."kb";
				if ($arrFile[$file]['Type'] == 'File' && pathinfo($file, PATHINFO_EXTENSION) == 'jpg') {
					$arrFile[$file]['PathDbImg'] = ltrim(str_replace($this->db->GetPath('Img'), '', $path), '/');
					$arrFile[$file]['DbImg'] = ltrim(str_replace($this->db->GetPath('Img'), '', $path).$file, '/');
				}
			}
		}
		else {
			echo "Directory not found";
		}

		return $arrFile;
	}

    /**
     * Render file array as html
     * @param array $arrFile
     * @param null $Type
     * @param null $Filter
     */
    public function render($arrFile, $Type = NULL, $Filter = NULL) {
		// get image data (Id, Img, ImgTitle) from database and compare it with file data to
		// see if image is unprocessed or already done and to add additional data

		$Folder = ltrim(str_replace($this->db->GetPath('Img'), '', $arrFile[0]['Current']), '/');
		$arrDbImg = $this->GetDbData($Folder);

		// File explorer
		if ($Type == 'File') {
			echo '<div class="FileExplorer">';
			echo '<br><span style="font-size: 80%">'.$arrFile[0]['Current'].'</span>';
			array_shift($arrFile);	// remove entry current
			echo '<table>';
			foreach ($arrFile as $File) {
				if ($File['Type'] == 'Dir') {
					echo '<tr class="'.$File['Type'].'">';
					echo '<td><a href="'.$File['Path'].($File['Name'] == '..' ? '' : $File['Name'].'/').'"><img style="width: inherit !important" src="'.$this->db->GetPath('WebRoot').'dbprivate/layout/images/folder.gif"/>'.$File['Name'].'</a></td>';
					echo '<td></td>';
					echo "</tr>\n";
				}
				else {
					if ($Filter == 1 && array_key_exists($File['Img'], $arrDbImg)) {
						continue;
					}
					if ($Filter == 2 && !array_key_exists($File['Img'], $arrDbImg)) {
						continue;
					}
					echo '<tr class="'.$File['Type'].'">';
					echo '<td';
					if (array_key_exists($File['DbImg'], $arrDbImg)) {
						// image file is already in db
						echo ' class="MarkDone"';
						// remove from array -> remaining items are not in file system
		//				unset($arrDbImg[$File['DbImg']]);
					}
					else {
						echo ' class="MarkOpen"';
					}
					echo '><img src="'.$File['Path'].$File['Name'].'"';
					if (array_key_exists($File['DbImg'], $arrDbImg)) {
						echo ' id="'.$arrDbImg[$File['DbImg']]['Id'].'"';
					}
					echo '/></td>';
					echo '<td style="text-align: right;">';
					echo (array_key_exists($File['DbImg'], $arrDbImg) && $arrDbImg[$File['DbImg']]['ImgTitle'] != '' ? $arrDbImg[$File['DbImg']]['ImgTitle'].'<br/>' : '');
					echo $File['Name'].'<br/>';
					echo $File['Size'];
					echo array_key_exists($File['DbImg'], $arrDbImg) ? '<br/>ID '.$arrDbImg[$File['DbImg']]['Id'] : '';
					echo '</td>';
					echo '</tr>';

					if (array_key_exists($File['DbImg'], $arrDbImg)) {
						// remove from array -> remaining items are not in file system
						unset($arrDbImg[$File['DbImg']]);
					}
				}
			}
			// loop through remaining records and mark them as missing
			foreach ($arrDbImg as $img) {
				echo '<tr class="File">';
				echo '<td class="MarkMissing"><img src="../dbprivate/layout/images/missing.gif" id="'.$img['Id'].'"></td>';
				echo '<td style="text-align: right;">';
				echo $img['ImgTitle'] != '' ? $img['ImgTitle'].'<br/>' : '';
				echo $img['Img'].'<br/>';
				echo 'ID '.$img['Id'].'</td>';
				echo '</tr>';
			}
			echo '</table></div>';
		}

		// Image Explorer
		else if ($Type == 'Image') {
			echo '<div class="ImgExplorer"><span style="font-size: 80%">'.$arrFile[0]['Current'].'</span>';
			array_shift($arrFile);
			foreach ($arrFile as $File) {
				if ($File['Type'] == 'Dir') {
					//			echo '<a href="'.$File['Path'].($File['Name'] == '..' ? '' : $File['Name']).'/"><img style="width: inherit !important" src="'.$this->GetPath('WebRoot').'layout/images/folder.gif"/>'.$File['Name'].'</a><br/>';
				}
				else {
					if ($Filter == 1 && array_key_exists($File['Img'], $arrDbImg)) {
						continue;
					}
					if ($Filter == 2 && !array_key_exists($File['Img'], $arrDbImg)) {
						continue;
					}
					echo '<div'.(array_key_exists($File['Img'], $arrDbImg) ? ' class="Done"' : ' class="NotDone"').'>';
					echo '<img src="'.$File['Path'].$File['Name'].'"';
					echo (array_key_exists($File['Img'], $arrDbImg) ? 'id="'.$arrDbImg[$File['Img']].'"' : '').'/><br/>';
					echo $File['Name'].' '.$File['Size'];
					echo '</div>';
				}
			}
			echo '</div>';
		}
	}

	/**
	 * Used to check if image file is already in db or not.
	 *
	 * Returns an array with
	 * @return array
	 * @param string $Folder
	 */
	private function GetDbData($Folder) {
		$arr = [];
		$Sql = "SELECT Id, ImgFolder||'/'||ImgName Img, ImgTitle FROM Images WHERE ImgFolder = :Folder ORDER BY ImgName ASC";
		$Stmt = $this->db->db->prepare($Sql);
		$Stmt->bindParam(':Folder', trim($Folder, '/'));
		$Stmt->execute();
		while ($Row = $Stmt->fetch(PDO::FETCH_ASSOC)) {
			$arrTemp = [];
			foreach ($Row as $Col => $Val) {
				$arrTemp[$Col] = $Val;
			}
			$arr[$Row['Img']] = $arrTemp; // e.g. $Row['Img'] = 'us002/us002-001.jpg'
		}
		return $arr;
	}
}