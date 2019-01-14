/**
 * Class to create form field filter.
 * 
 * You can either filter a HTMLElement (FldTarget), which is permanently visible, or
 * the element is only displayed when a key is pressed.
 * 
 *  @requires Tools class_tools.js
 */
 
const FORMFILTER_NORMAL = 1;
const FORMFILTER_SHOWHIDE = 2;

/**
 * @constructor
 * @param const
 */ 
function Filter() {
	if (arguments[0] && arguments[0] == 2) {
		this.Type = 2;	// 'FORMFILTER_SHOWHIDE or FORMFILTER_NORMAL (default)
	}
	else {
		this.Type = 1;
	}
	this.Tools = new Tools();
	this.PosSet = false;	// set to true if Filter's coordinates are set (to check -> do only once)
	this.MinChar = 3;			// numbers of input characters before filtering
} 

/**
 * Setup the filter.
 * 
 * User after page load. Otherwise HTMLElements not availble
 * Takes the input from a HTMLInputElement (source) and uses it to filter the items of a HTMLSelectElement (target).
 * 
 * @param string FldTarget
 * @param string FldSource
 */
Filter.prototype.init = function(FldSource, FldTarget) {
	var Self = this;
	this.FldSource = d.getElementById(FldSource);	// HTMLInputElement used as source. Typed text is used to filter select list
	this.FldTarget = d.getElementById(FldTarget);	// HTMLSelectElement to be filtered
	if (!this.FldSource) { alert('Error: source element does not exist.'); }
	if (!this.FldTarget) { alert('Error: source element does not exist.'); }
	
	// copy select to array for filtering
	var Children = this.FldTarget.getElementsByTagName('option');
	this.arrFilterAll = new Array();					// stores all select/span elements before filtering
	var i = Children.length - 1;
	for (; i > -1; i--) {
		var Node = Children[i].cloneNode(true);	// store copy of node with all attributes and children
		var El = Node;
		while (El && El.nodeType != 3) {							// find first text node. <span> element can contain other elements such as <a>
			El = El.firstChild;
		}
		var Text = El.nodeValue;
		this.arrFilterAll[i] = new Array(Node, Text);
	}
	this.FldSource.addEventListener('keyup', function(e) {
		if (Self.FldSource.value.length >= Self.MinChar) {	// start filtering after minimum number of typed chars
			Self.FilterList(e);
			Self.PosFilter();
			Self.HandleKey(e);
			if (Self.Type == 2) { Self.ShowFilter(); }
		}
	}, true);
	if (this.Type == 2) {
		this.FldSource.addEventListener('blur', function(e) { Self.HideFilter(); }, true);
	}
}

/**
 * Position filter element below source HTMLInputElement
 */
Filter.prototype.PosFilter = function() {
	if (this.Type == 2) {
		if (!this.PosSet) {	// position filter only once
			var H = parseInt(document.defaultView.getComputedStyle(this.FldSource, null).getPropertyValue("height"));
			var P = parseInt(document.defaultView.getComputedStyle(this.FldSource, null).getPropertyValue("padding-top"));
			var B = parseInt(document.defaultView.getComputedStyle(this.FldSource, null).getPropertyValue("border-left-width"));
			this.FldTarget.style.top = this.Tools.GetPos(this.FldSource)[1] + H + (P*2) + B + 'px';
			this.FldTarget.style.left = this.Tools.GetPos(this.FldSource)[0] + B + 'px';
			this.PosSet = true;
		}
	}
}

/**
 * Hide filter.
 */
Filter.prototype.ShowFilter = function() {
	this.FldTarget.style.display = 'block';
}

/**
 * Show filter.
 */
Filter.prototype.HideFilter = function() {
	this.FldTarget.style.display = 'none';
}

/**
 * Handle keyboard actions.
 * 
 * @param object e KeyboardEvent
 */
Filter.prototype.HandleKey = function(e) {
	var Key = e.keyCode;	
	if (Key == 27) { // esc key hide filter 
		this.HideFilter();
	}
	else if (Key == 40) {	// key down
		if (this.FldTarget.selectedIndex == this.FldTarget.length - 1) { this.FldTarget.selectedIndex = 0; }
		else { this.FldTarget.selectedIndex++; }
	}
	else if (Key == 38) {	// key up
		if (this.FldTarget.selectedIndex == 0) { this.FldTarget.selectedIndex = this.FldTarget.length - 1; }
		else { this.FldTarget.selectedIndex--; }
	}
	if (Key == 40 || Key == 38) {
	 	this.FldSource.value = this.FldTarget[this.FldTarget.selectedIndex].text;
	}
}

/**
 * Does the actual filtering of the HTMLSelectElement.
 */
Filter.prototype.FilterList = function(e) {
	if (e.keyCode == 40 || e.keyCode == 38) { return; }	// do not filter on arrow key down/up
	var arrMatch = new Array();										// store matches
	var Val = this.FldSource.value;								// text used as filter
	if (Val.lastIndexOf(" ") == Val.length - 1) {	// remove trailing spaces, IE doesn' split last separator
	 	// copy back original array when using space key (display unfiltered list) -> next word
		arrMatch = this.arrFilterAll;
	}
	else {	// use last word as filter input
		Val = Val.split(/\s/);
		Val = Val.pop();
		Val = new RegExp(Val, "i");

		// create new array with matches
		var i = 0;	
		var Len = this.arrFilterAll.length;
		for (; i < Len; i++) {
			if (Val.test(this.arrFilterAll[i][1])) {
				arrMatch[arrMatch.length] = new Array(this.arrFilterAll[i][0], this.arrFilterAll[i][1]);
			}
		}
	}
	// write array with matches back to form select array 
	i = 0;
	ListLen = arrMatch.length;
	while (this.FldTarget.firstChild) {
		this.FldTarget.removeChild(this.FldTarget.firstChild);
	}
	for (; i < ListLen; i++) {
		this.FldTarget.appendChild(arrMatch[i][0]);
	}
}


