/******************
 List class:
 use with form selects or div-span lists
 adds usefull methods such as add or remove item, etc.
 v1.0, 24.08.2006
 *******************/


function List() {
	this.El = null;			// El is set through init method. Html elements are only available after page load
	this.arrEvent = [];	// store registered events
	this.Type = 'SELECT';
}

List.prototype.init = function(Id) {
	// html elements are only available after page load
	// -> use init function to set reference
	this.El = d.getElementById(Id);
	this.El.style.overflow = "auto";
	this.ElCopy = this.El.innerHTML;	// store old children for reset method
	// autodetect list type
	this.Type = this.El.nodeName.toUpperCase();
	if (this.Type == 'DIV') {
		this.El.style.overflow = "auto";
	}
}

List.prototype.RegisterEvent = function() {
	// provide events for each item in list and make this referring to the right object (owner)
	var Event = arguments[0];
	var Func = arguments[1];
	var arrArgs = [];
	// in some browsers you can not use array methods on arguments -> copy to array
	var i = 2;
	for (; i < arguments.length; i++) {
		arrArgs.push(arguments[i]);
	}
	i = 0;
	if (this.Type == 'SELECT') {
		var arrItem = this.El.getElementsByTagName('option');
	}
	else if (this.Type == 'DIV') {
		var arrItem = this.El.getElementsByTagName('div');
	}
	for	(; i < arrItem.length; i++) {
		arrItem[i].addEventListener(Event, function(e) { arrArgs.unshift(e); Func.apply(this, arrArgs); }, false);
	}
	this.arrEvent[this.arrEvent.length] = [Event, Func, arrArgs];
}

List.prototype.RemoveItem = function() {
	// arg = item to be removed
	var Len = arguments.length;
	var ElRemoved = null;
	if (Len > 0) {
		// remove specified items
		var i = 0;
		for (; i < Len; i++) {
			this.El.removeChild(arguments[i]);
		}
	}
	else {	// remove all
		while (this.El.hasChildNodes()) {
			this.El.removeChild(this.El.firstChild);
		}
	}
}

List.prototype.AddItem = function(Item) {
	// add item at end of list
	this.El.appendChild(Item);
}

List.prototype.AddItemBefore = function(Item, El) {
	// at item before other item
	if (El) {
		this.El.insertBefore(Item, El);
	}
	else {
		// add item at beginning of list
		this.El.insertBefore(Item, this.El.firstChild);
	}
}

List.prototype.AddItemAfter = function(Item, El) {
	// at item after other item
	if (El) {
		// there is no insertAfter DOM method
		El = El.nextSibling;
		this.El.insertBefore(Item, El);
	}
	else {
		// add item at end of list
		this.El.appendChild(Item);
	}
}

List.prototype.SelectAll = function() {
	// TODO: implement for other elements than SELECT
	var arrChild = this.El.childNodes;
	var i = 0;
	var Len = arrChild.length;
	for (; i < Len; i++) {
		var Child = arrChild[i];
		if (Child && Child.nodeType == 1) {	// 1 =	element node, there are empty nodes (white space)
			Child.selected = true;
		}
	}
}

List.prototype.DeselectAll = function() {
	// TODO: implement for other elements than SELECT
	var arrChild = this.El.childNodes;
	var i = 0;
	var Len = arrChild.length;
	for (; i < Len; i++) {
		var Child = arrChild[i];
		if (Child && Child.nodeType == 1) {	// 1 =	element node, there are empty nodes (white space)
			Child.selected = false;
		}
	}
}

List.prototype.Reset = function() {
	// TODO: adapt for other elements than SELECT
	this.El.selectedIndex = -1;
	this.El.innerHTML = this.ElCopy;
	// copy events back
	var	i = 0;
	Len = this.arrEvent.length;
	for (; i < Len; i++) {
		var Event = this.arrEvent[i][0];
		var Func = this.arrEvent[i][1];
		var Args = this.arrEvent[i][2];
		this.RegisterEvent(Event, Func, Args);
	}
}
