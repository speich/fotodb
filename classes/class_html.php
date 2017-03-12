<?php
/*
 * HTML class developed by Simon Speich
 * you can use and alter this freely as long as you keep this message
 */


define("HTML_OPTION_VALUE", 'Value');		// use select <option value> attribute
define("HTML_OPTION_TEXT", 'Txt');			// use text <option>text</option> to set selected

class Html {
	protected $Id = false;
	protected $CssClass = false;
	protected $CssStyle = false;

	public function SetId($Id) { $this->Id = $Id; }	// set by child constructors
	public function GetId() { 
		if ($this->Id) { return $this->Id; }
		else { return false; }
	}
	public function SetCssClass($Class) { $this->CssClass = $Class; }
	public function GetCssClass() {
		if ($this->CssClass) { return $this->CssClass; }
		else { return false; }
	}
	public function SetCssStyle($Style) { $this->CssStyle = $Style; }
	public function GetCssStyle() {
		if ($this->CssStyle) { return $this->CssStyle; }
		else { return false; }
	}
}

class HtmlDivList extends Form {
	private $arrData = array();
	
	function __construct($arrData) {
		$this->arrData = $arrData;		
	}
	
	public function Render() {
		echo '<div style="overflow: auto;">';
		foreach ($this->arrData as $Data) {
			echo '</div>';
		}
	}
}

class Form extends Html {
	protected $Label = false;
	protected $LabelName;
	protected $Disabled = false;
	protected $Selected = false;
	
	public function SetDisabled($Bool) { $this->Disabled = $Bool; }
	
	public function SetLabel($Label) {
		$this->LabelName = $Label;
		$this->Label = true;
	}
	
	public function SetSelected() { $this->Selected = true; }	
	
	public function GetLabel() {
		if ($this->Label) { return $this->LabelName; }
		else { return false; }
	}
	
}

class HtmlRadioButton extends Form {
	private $Val;
	private $Grouped = false;	// radio buttons can be in groupes with the same name
	private $GroupName;				// name of radio group
	
	public function __construct($Id, $Val) {
		$this->SetId($Id); 
		$this->Val = $Val;
	}
	
	public function SetGroup($Name) {
		$this->GroupName = $Name;
		$this->Grouped = true;
	}
	
	public function GetGroup() {
		if ($this->Grouped) { return $this->GroupName; }
		else { return false; }
	}
	
	function Render() {
		if ($this->Label) {	echo '<label for="'.$this->GetId().'">'.$this->GetLabel()."</label>\n"; }
		echo '<input id="'.$this->GetId().'"';
		if ($this->Grouped) { echo ' name="'.$this->GetGroup().'"'; }
		echo ' type="radio" value="'.$this->Val.'"';
		if ($this->Selected) { echo ' checked="checked"'; }
		if ($this->Disabled) { echo ' disabled="disabled"'; }
		if ($this->CssClass) { echo ' class="'.$this->CssClass.'"'; }
		if ($this->CssStyle) { echo ' style="'.$this->CssStyle.'"'; }
		echo " />";
	}
}

class HtmlSelectFld extends Form {
	// this class creates a Html select field
	// mehthod accepts either a one or a two dimensional array. With 
	// 1-dim a new index is created to use in the value fields, otherwise
	// first column is used as index, second colum as text
	private $arrOption;
	private $Multiple = false;
	
	// set selected field
	private $UseTxt = false;
	private $SelectedVal;					// store value/txt to compare
	private $DefaultVal = false;	// first item in select field
	private $Name = "";						// set name attribute separately, default = Id in constructor
																// e.g. id="Test" name="Test[]" post PHP array [] not valid JS as an id

	public function __construct($Id, $arrOption) {
		$this->SetId($Id);
		$this->arrOption = $arrOption;
		$this->Name = $Id;
	}

	public function SetMultiple() { $this->Multiple = true; }
	
	/**
	 * Set HTMLOptionElement to selected.
	 *
	 * @param string|number $Val
	 * @param constant $Type [optional]
	 */
	public function SetSelected($Val, $Type = null) {
		if (is_bool($Val)) { return false; }	// prevent value="0" to be set as selected
		if (isset($Type) && $Type == HTML_OPTION_TEXT) { $this->UseTxt = true; }
		if (!is_array($Val)) { $Val = array($Val); }	// render method needs array (to allow for multiple selected values)
		$this->SelectedVal = $Val;
		$this->Selected = true;
	}
	
	public function SetName($Name) { $this->Name = $Name; }
	
	/**
	 * Set default value of first item of select box.
	 *
	 * @param string $Txt Text to display as first item.
	 */
	public function SetDefaultVal($Txt) {
		$this->DefaultVal = $Txt;
	}

	public function Render() {
		$Html = '<select id="'.$this->GetId().'" name="'.$this->Name.'"';
		if ($this->CssClass) { $Html .= ' class="'.$this->CssClass.'"'; }
		if ($this->CssStyle) { $Html .= ' style="'.$this->CssStyle.'"'; }
		if ($this->Multiple) { $Html .= ' multiple="multiple"'; }
		if ($this->Disabled) { $Html .= ' disabled="disabled"'; }
		$Html .= ">\n";
		if ($this->DefaultVal) { $Html .= '<option value="-1">'.$this->DefaultVal.'</option>'; }
		
		$i = 0;
		foreach ($this->arrOption as $Row) {
			$Html .= '<option';
			if (count($Row) > 1) {
				// 2-dim array: use first col as value
				$Html .=  ' value="'.$Row[0].'"';
				if ($this->Selected) {
					foreach ($this->SelectedVal as $Val) {
						if ($this->UseTxt) { 
							if ($Row[1] == $Val) { $Html .= ' selected="selected"'; }
						}
						else if ($Row[0] == $Val) { $Html .= ' selected="selected"'; }
					}
				}	
				$Html .= '>'.$Row[1];
			}
			else { // 1-dim: use created index
				$Html .= ' value="'.$i++.'"';				
				if ($Row[0] == $this->SelectedVal) { $Html .= ' selected="selected"'; }
				$Html .= '>';
			}
			$Html .= "</option>\n";
		}
		$Html .= "</select>\n";
		echo $Html;	
	}
}

class HtmlCheckBox extends Form {
	
	public function __construct($Id, $Val) {
		$this->SetId($Id); 
		$this->Val = $Val;
		$this->Name = $Id;
	}
	
	function Render() {
		if ($this->Label) {	echo '<label for="'.$this->GetId().'">'.$this->GetLabel()."</label>\n"; }
		echo '<input id="'.$this->GetId().'" name="'.$this->Name.'"';
		echo ' type="checkbox" value="'.$this->Val.'"';
		if ($this->Selected) { echo ' checked="checked"'; }
		if ($this->Disabled) { echo ' disabled="disabled"'; }
		if ($this->CssClass) { echo ' class="'.$this->CssClass.'"'; }
		if ($this->CssStyle) { echo ' style="'.$this->CssStyle.'"'; }
		echo " />";
	}
	
}
	
//echo "inc_hml done.<br/>";
?>