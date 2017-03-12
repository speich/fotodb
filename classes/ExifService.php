<?php
namespace photoXplorer;

class ExifService {

	private $exiftool = '/cgi-bin/Image-ExifTool-10.24/exiftool';
	private $exiftoolParams = '-config /cgi-bin/.ExifTool_config -g -s2 -j';
	//private $exiftoolParams = '-G';

	/**
	 * ExifService constructor.
	 * @param string $lang
	 */
	public function __construct($lang = 'en') {
		$this->exiftoolParams.= ' -lang '.$lang;	// TODO: ceck input which is passed as an argument to exiftool
	}

	public function checkForUpdate() {
		// TODO: exiftool provides an rss feed I could use to automatically update
		// http://owl.phy.queensu.ca/~phil/exiftool/rss.xml
		// check element: <lastBuildDate>Mon, 13 Jun 2016 09:30:11 -0400</lastBuildDate>
	}

	/**
	 * Checks if a XMP sidecar file exists.
	 * @param $img
	 * @return bool
	 */
	public function hasSeparateXmpFile($img) {
		$imageNoExt = substr($img, 0, strrpos($img, '.'));   // remove file extension

		return file_exists($imageNoExt.'.'.'xmp');
	}

	/**
	 * Returns exif data as a multidimensional array.
	 * Calls the exif tool for NEF and xmp
	 * @param string $img full path of image
	 * @return array mixed
	 */
	public function getData($img) {
		// TODO: make this work with other than NEF by using ExifService::originalImageExists
		$imageNoExt = substr($img, 0, strrpos($img, '.'));   // remove file extension
		$tool = __DIR__.'/..'.$this->exiftool.' '.$this->exiftoolParams;
		exec($tool.' '.$imageNoExt.'.NEF', $data);
		$data = implode('', $data);
		$data1 = json_decode($data, true);
		$data1 = array_pop($data1);

		$data2 = [];
		if ($this->hasSeparateXmpFile($img)) {
			exec($tool.' '.$imageNoExt.'.xmp', $data);   // note: exec appends the data
			$data = implode('', $data);
			$data2 = json_decode($data, true);
			$data2 = array_pop($data2);
		}

		return array_merge($data1, $data2);
	}

	/**
	 * Print exif data as a HTML table.
	 * @param array $data array returned from exiftool
	 * @return string HTML table
	 */
	public function render($data) {
		$str = '<table class="exifData">';
		foreach ($data as $heading => $group) {
			$str.= '<tbody>';
			if (is_array($group)) {
				ksort($group);
				$str.= '<tr><th scope="rowgroup" colspan="2">'.$heading.'</th></tr>';
				foreach($group as $key => $item) {
					$str .= '<tr><th scope="row">'.$key.'</th><td>'.(is_array($item) ? implode(',',$item) : $item).'</td></tr>';
				}
			}
			else {
				$str.= '<tr><th scope="row">'.$heading.'</th><td>'.$group.'</td></tr>';
			}
			$str.= '</tbody>';
		}
		$str.= '</table>';

		return $str;
	}
}