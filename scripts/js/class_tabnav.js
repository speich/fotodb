/**
 * Class for HTMLDivElement tab bar.
 * 
 * With each tab in the tab bar, there is also an associated tab target,
 * which is displayed, when the tab is clicked.
 * 
 * @constructor
 */
function TabNav() {
	this.arrTab = new Array();	// holds all created tabs
	this.ColorTabActive = '#DFD0C2';
	this.ColorTabInactive = 'inherit';
}


/**
 * Create tab.
 * 
 * @param integer TabIndex Index of tab from left to right
 * @param string TargetId Id of element to show on tab click
 * @return object
 */
TabNav.prototype.Create = function(TabIndex, TargetId) {
	var Self = this;
	var obj = new Object();
	this.arrTab.push(obj);
	obj.ElTarget = d.getElementById(TargetId);
	if (!obj.ElTarget) { alert('Error: HTMLElement with id ' + TargetId + ' not found.'); return false; }
	obj.El = d.getElementById('TabNav').getElementsByTagName('div')[TabIndex-1];
	if (!obj.El) { alert('Error: HTMLDivElement with TabIndex ' + TabIndex + ' not found.'); return false; }
	obj.Show = function() { Self.ShowTab(obj); }
	obj.El.addEventListener('click', obj.Show, false);
	return obj;
}

/**
 * Show tab and tab target.
 */
TabNav.prototype.ShowTab = function(Tab) {
	this.HideTab();	// hide previously shown tab first
	Tab.ElTarget.style.visibility = 'visible';
	Tab.visible = true;
	//  show also all children of Target, not only target itself
	var Nodes = Tab.ElTarget.getElementsByTagName('*');
	var j = Nodes.length - 1;
	for (; j > -1; j--) {
		Nodes[j].style.visibility = 'visible';
	}
	Tab.El.style.backgroundColor = this.ColorTabActive;
}

/**
 * Hide all tabs.
 */
TabNav.prototype.HideTab = function() {
	var i = this.arrTab.length - 1;
	for (; i > -1; i--) {
		this.arrTab[i].visible = false;
		this.arrTab[i].El.style.backgroundColor = this.ColorTabInactive;
		this.arrTab[i].ElTarget.style.visibility = 'hidden';
		//  hide also all children of Target, not only target itself
		var Nodes = this.arrTab[i].ElTarget.getElementsByTagName('*');
		var j = Nodes.length - 1;
		for (; j > -1; j--) {
		 Nodes[j].style.visibility = 'hidden';
		}
	}
}