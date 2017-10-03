<?php
/**
 * This file contains two class to create a navigation menu.
 * @author Simon Speich
 * @package NAFIDAS
 */

/**
 * Simple recursive php menu with unlimited levels which creates an unordered list
 * based on an array.
 * 
 * Each item can have its own js event handler. 
 * To increase performance only open menus are used in recursion unless you set
 * the whole menu to be open by setting the property AutoOpen = true;
 * 
 * @package NAFIDAS
 */
class MenuItem {
	public $Id = null;
	public $ParentId = null;
	public $LinkTxt = '';
	public $LinkUrl = '';
	
	/**
	 * render this items children.
	 * @var bool $RenderChild
	 */
	private $ChildrenToBeRendered = false;
	private $Active = false;	 
	protected $EventHandler = null;	// hold the event listener for an item 
	
	/**
	 * Constructs the menu item.
	 * @param integer|string $Id unique id
	 * @param integer|string $ParentId id of parent item
	 * @param string $LinkTxt link text
	 * @param string [$LinkUrl] link url
	 * @param string [$EventHandler] event listener
	 */
	public function __construct($Id, $ParentId, $LinkTxt, $LinkUrl = null, $EventHandler = null) {
		$this->Id = $Id;
		$this->ParentId = $ParentId;
		$this->LinkTxt = $LinkTxt;
		$this->LinkUrl = $LinkUrl;
		$this->EventHandler = $EventHandler;
	}
	
	/** Get item property if children will be rendered */
	public function GetChildrenToBeRendered() { return $this->ChildrenToBeRendered; }
	
	/**
	 * Set item property if children will be rendered.
	 * @param bool [$ChildToBeRendered]
	 */
	public function SetChildrenToBeRendered($ChildrenToBeRendered = true) {
		$this->ChildrenToBeRendered = $ChildrenToBeRendered;
	}
	
	/**
	 * Set item to be active.
	 * @param bool [$Active]
	 */
	public function SetActive($Active = true) {
		$this->Active = $Active;
	}
	
	/**
		* Get item active status.
	 */
	public function GetActive() {
		return $this->Active;
	}
	
}

/**
 * Creates menu items.
 * A menu is made of menu items.
 * @package NAFIDAS
 */
class Menu extends MenuItem {
	/**
	 * Holds array of menu items.
	 * @var array menu items
	 */	 
	public $arrItem = array();
	
	/** 
	 * Holds html string of created menu.
	 * @var string menu string
	 */
	private $strMenu = '';
	
	/** All child menus are rendered by default
	 * @var bool render children
	 */
	public $AllChildrenToBeRendered = true;
	
	/** Automatically mark item and all its parents as active if its url is same as url of current page.
	 * @var bool
	 */ 
	public $AutoActive = true;
	
	/**
	 * Sets the url matching pattern of $AutoActive property.
	 * 1 = item url matches path only, 2 = item url patches path + query, 3 item url matches any part of path + query 
	 * @var integer
	 */
	public $AutoActiveMatching = 1;
	
	/** Flag to mark first ul tag in recursive method */
	private $FirstUl = true;
	
	/** Menu CSS class name */
	public $CssClass = null;
	
	/** Menu CSS id name */
	public $CssId = null;
	
	/** Active item CSS class name */
	public $CssItemActiveChild = 'MActiveChild';
	
	/** Active item CSS class name */
	public $CssItemActive = 'MActive';
	
	
	/**
	 * Constructs the menu.
	 * You can provide a 2-dim array with all menu items 
	 * or use the add method for each item singedly.
	 * @param string [$CssId] HTMLIdAttribute
	 * @param string [$CssClass] HTMLClassAttibute
	 * @param array [$arrItem] array with menu items
	 */
	public function __construct($CssId = null, $CssClass = null, $arrItem = null) {
		if (!is_null($arrItem)) {
			foreach ($arrItem as $Item) {
				$this->arrItem[$Item[0]] = new MenuItem($Item[0], $Item[1], $Item[2], (array_key_exists(3, $Item) ? $Item[3] : null), (array_key_exists(4, $Item) ? $Item[4] : null));
			}
		}
		if (!is_null($CssClass)) {
			$this->CssClass = $CssClass;
		}
		if (!is_null($CssId)) {
			$this->CssId = $CssId;
		}
	}
	
	/**
	 * Add a new menu item.	 * 
	 * Array has to be in the form of:
	 * array(Id, ParentId, LinkTxt, optional LinkUrl, optional event handler);
	 * You can add new items to menu as long as you haven't called the render method.
	 * @param array $Arr menu item
	 */
	public function Add($Arr) {
		$this->arrItem[$Arr[0]] = new MenuItem($Arr[0], $Arr[1], $Arr[2], (array_key_exists(3, $Arr) ? $Arr[3] : null), (array_key_exists(4, $Arr) ? $Arr[4] : null));
	}
	
	/**
	 * Check if menu item has at least one child menu.
	 * @return bool
	 * @param string|integer $Id item id
	 */
	private function CheckChild($Id) {
		$Found = false;
		foreach ($this->arrItem as $Val) {
			if ($Val->ParentId === $Id) {
				$Found = true;
				break;
			}
		}
		return $Found;
	}
	
	/**
	 * Add an javascript event handler to a menu item.
	 * 
	 * Sets a js event 
	 * @param integer|string $Id menu id
	 * @param string $EventHandler js event handler
	 */
	public function SetEventHandler($Id, $EventHandler) {
		foreach ($this->arrItem as $Item) {
			if ($Item->Id === $Id) {
				$Item->EventHandler = $EventHandler;
			}
		}
	}
	
	/**
	 * Sets an menu item to active if url matches the set pattern.
	 * 1 = item url matches path only, 2 = item url patches path + query, 3 item url matches path + any part of query 
	 * @param object $Item MenuItem
	 */
	public function SetActive($Item) {
		$Url = $_SERVER['REQUEST_URI'];
		$arrUrl1 = parse_url($Url);
		$arrUrl2 = parse_url($Item->LinkUrl);
		switch($this->AutoActiveMatching) {
			case 1:	
				if ($arrUrl1['path'] == $arrUrl2['path']) {
					$Item->SetActive();
				}
				break;
			case 2:
				if ($arrUrl1['path'].'?'.$arrUrl1['query'] == $Item->LinkUrl) {
					$Item->SetActive();
				}
				break;
			case 3:
				foreach ($_GET as $Var => $Val) {
					echo "{$arrUrl1['path']}'?'$Var == {$Item->LinkUrl}<br>";
					if ($arrUrl1['path'].'?'.$Var == $Item->LinkUrl) {
						$Item->SetActive();
					}
				}
				break;
		}				
	}
	
	/**
	 * Creates the menu.
	 * @return string
	 * @param string|integer $ParentId seed
	 */
	private function Create($ParentId) {
		$this->strMenu.= '<ul';
		if ($this->FirstUl) {
			if (!is_null($this->CssClass)) {
				$this->strMenu.= ' class="'.$this->CssClass.'"';
			}
			if (!is_null($this->CssId)) {
				$this->strMenu.= ' id="'.$this->CssId.'"';
			}
			$this->FirstUl = false;
		}
		$this->strMenu.= ">\n";
		foreach ($this->arrItem as $Item) {
			if ($Item->ParentId === $ParentId) {
				if ($Item->GetActive()) {
					if ($this->CheckChild($Item->Id)) {
						$this->strMenu.= '<li class="'.$this->CssItemActiveChild.'">';
					}
					else {
						$this->strMenu.= '<li class="'.$this->CssItemActive.'">';
					}
				}
				else {
					$this->strMenu.= '<li>';
				}
				if ($Item->LinkUrl != '') {
					$this->strMenu.= '<a href="'.$Item->LinkUrl.'"'.(is_null($Item->EventHandler) ? '' : ' '.$Item->EventHandler).'>';
				}
				else {
					$this->strMenu.= '<a'.(is_null($Item->EventHandler) ? '' : ' '.$Item->EventHandler).'>';	// for css we have the same structure, with or without a link
				}
				$this->strMenu.= $Item->LinkTxt;
				$this->strMenu.= '</a>';
				if ($this->CheckChild($Item->Id)) {
					if (($this->AllChildrenToBeRendered || $Item->GetChildrenToBeRendered())) {
						$this->Create($Item->Id);
						$this->strMenu.= "</ul>\n";
					}
				}
				$this->strMenu.= "</li>\n";
			}
		}
		return $this->strMenu;
	}
	
	/**
	 * Returns a HTML string of the menu.
	 * @return string
	 */
	public function Render() {
		// Set child/parent items to render/active before rendering
		foreach ($this->arrItem as $Item) {
			if ($this->AutoActive) {
				$this->SetActive($Item);
			}
			if ($this->AllChildrenToBeRendered || $Item->GetActive()) {
				// set also item's parents to active
				$ParentId = $Item->ParentId;
				while (array_key_exists($ParentId, $this->arrItem)) {
					$this->arrItem[$ParentId]->SetChildrenToBeRendered();
					$this->arrItem[$ParentId]->SetActive();
					$ParentId = $this->arrItem[$ParentId]->ParentId;
				}
			}			
		}	
		if (count($this->arrItem) > 0) {
			$str = $this->Create(reset($this->arrItem)->ParentId);
			return $str."</ul>\n";
		}
		else {
			return '';
		}
	}
}
?>