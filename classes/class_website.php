<?php
class Website	{
	private $Url, $Page, $Dir, $IP, $Host, $DocRoot, $WebRoot;
	private $Query = '';	// stores the querystring without the leading question mark
	private $LastUpdate = "Letzte Aktualisierung: 03.09.2008";
	private $DefaultPage = 'default.php';

	/**
	 * @constructor
	 */
	public function __construct() {
		$arrUrl = parse_url($_SERVER["REQUEST_URI"]);
		$arrPath = pathinfo($arrUrl['path']);
		$this->Page = $arrPath['basename'];
		$this->Dir = $arrPath['dirname'];
		$this->IP = $_SERVER['REMOTE_ADDR'];
		$this->Host = $_SERVER['HTTP_HOST'];
		$this->WebRoot = '/';
		$this->DocRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/').'/'.($this->WebRoot == '/' ? '' : $this->WebRoot);
		$this->Url = $_SERVER["REQUEST_URI"];
		$this->Query = array_key_exists('query', $arrUrl) ? $arrUrl['query'] : '';
	}
	
	/**
	 * Returns physical path to webroot including web root.
	 * @return string
	 */
	public function GetDocRoot() { return $this->DocRoot; }
	
	/**
	 * Sets the website root to a subfolder.
	 * 
	 * It is not always possible in a webproject to use relative paths.
	 * But with absolute or physical paths you could run into problems:
	 * If you want to move your project into another subfolder or you 
	 * publish your website into different folders
	 * e.g. www.mywebsite.ch and www.mywebsite.ch/developerversion/ 
	 * In these cases use the methods SetWebRoot() and GetWebRoot().
	 *
	 * @param string $Path webroot
	 */
	function SetWebRoot($Path) {
		$this->WebRoot = '/'.$Path;
	}
	
	/**
	 * Returns the website's root folder
	 * @return string
	 */
	function GetWebRoot() { return $this->WebRoot; }
	
	public function GetIP() { return $this->IP; }
	
	public function GetHost() {	return $this->Host; }
	
	public function GetUrl() { return $this->Url; }
	
	/**
	 * Returns the current web page.
	 * @return string
	 */
	public function GetPage() { 
		if ($this->Dir == '\\' || $this->Dir == '/') {
			return '';
		}
		else { 	return $this->Page; }
	}
	
	/**
	 * Returns information about the web directory.
	 * @return string
	 */	
	public function GetDir() { return $this->Dir; }
	
	/**
	 * Takes an array of key-value pairs as input and adds it to the query string.
	 * 
	 * If there is already the same key in the query string its value gets overwritten
	 * with the new value. Saves the added key-value pairs to be reused (query property is changed).
	 * @return string querystring
	 * @param array $arrQuery
	 */
	public function AddQuery($arrQuery) {
		if ($this->Query != '') {	// check if query var(s) already exists -> overwrite with new or append
			parse_str($this->Query, $arrVar);
			$arrQuery = array_merge($arrVar, $arrQuery);	// if arrays have same string keys, the later key will overwrite the previous
			$this->Query = http_build_query($arrQuery);		// update local $this->Query
		}	
		else {
			$this->Query = http_build_query($arrQuery);
		}
		return '?'.htmlspecialchars($this->Query);
	}
	
	/**
	 * Returns the current query string.
	 * 
	 * You can optionally add or remove key-value pairs from the returned querystring without changing it, 
	 * e.g. same as AddQuery or DelQuery but Query property remains unchanged.
	 * If second argument is an array, the first array is used to add, the second to delete.
	 * 1 = add (default), 2 = remove
	 * @return string query string
	 * @param array [$arrQuery]
	 * @param integer|array [$Modifier]
	 */
	public function GetQuery($arrQuery = null, $Modifier = 1) {
		if (is_null($arrQuery)) {
			if ($this->Query != '') {
				return '?'.htmlspecialchars($this->Query);
			}
			else {
				return '';
			}
		}
		else if (is_array($Modifier)) {	// all of second array is to delete
			$str = $this->GetQuery($Modifier, 2);
			$str = str_replace('?', '', $str);
			$str = html_entity_decode($str);
			parse_str($str, $arrVar);
			$arrQuery = array_merge($arrVar, $arrQuery);
			return '?'.htmlspecialchars(http_build_query($arrQuery));
		}
		else {	// first array is either add or delete, no second array
			if ($this->Query != '') {	// check if query var(s) already exists -> overwrite with new or append
				parse_str($this->Query, $arrVar);
				if ($Modifier == 1) {
					$arrQuery = array_merge($arrVar, $arrQuery);	// if arrays have same string keys, the later key will overwrite the previous
					return '?'.htmlspecialchars(http_build_query($arrQuery));		// update local $this->Query
				}
				else if ($Modifier == 2) {
					$arr = array();	// make array keys for array_diff_key
					foreach ($arrQuery as $QueryVar) {
						$arr[$QueryVar] = null;
					}
					$arrQuery = array_diff_key($arrVar, $arr);
					if (count($arrQuery) > 0) {
						return '?'.htmlspecialchars(http_build_query($arrQuery));
					}
					else {
						return '';
					}
				}
			}	
			else {
				if ($Modifier == 1){
					return '?'.htmlspecialchars(http_build_query($arrQuery));
				}
				else {
					return '';
				}	
			}
		}
	}
	
	/**
	 * Removes key-value pairs from querystring before returning it.
	 * @return array 
	 * @param array|string $arrQuery Object
	 */
	public function DelQuery($arrQuery) {
		if (!is_array($arrQuery)) {
			$arrQuery = array($arrQuery);
		}
		if ($this->Query != '') {
			foreach ($arrQuery as $QueryVar) {
				$Pattern = '/&?'.$QueryVar.'=[^\&]*/';
				$this->Query = preg_replace($Pattern, '', $this->Query);
			}
		}
		$this->Query = preg_replace('/^\&/', '', $this->Query); // if first key-value pair was removed change ampersand to questions mark
		return htmlspecialchars($this->GetQuery());
	}
	
	public function GetLastUpdate() { return $this->LastUpdate; }
	
	public function CheckLoggedIn() {
		if (!isset($_SESSION["LoggedIn"]) && $_SESSION["LoggedIn"] != 1) {
			if (func_num_args() == 0) { header("Location: ".$this->DefaultPage); }
			else { header("Location: ".func_get_argument(0)); }
		}
	}
	
	public function ArrayMultiSort($Arr, $Col) {
		if (count($Arr) == 0) { return false; }
		// obtain list of columns for multisort
		foreach($Arr as $Key => $Val) { 
			$arrTxt[$Key] = $Val[$Col];
		}
		array_multisort($arrTxt, SORT_ASC, SORT_STRING, $Arr);
		return $Arr;
	}
}