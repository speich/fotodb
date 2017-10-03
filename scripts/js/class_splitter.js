/**
 * @projectDescription This file contains a the class to create a draggable splitter.
 * @author Simon Speich
 */

var d = document;	// shortcut

/**
 * Creates the splitter object.
 * A splitter consists of a handle and an element left and right to it.
 * When the handle is dragged the element left to it gets wider and the element
 * right to it smaller, or the other way round.
 * @constructor
 */
function Splitter() {
  this.LastX = 0;
	this.OrigX = null;		// default width of LeftEl
	this.EventRef1 = null;// Stores reference for event removal in EndDrag method
	this.LeftEl = null;		// element left of resize handle
	this.RightEl = null;	// element right of resize handle
	this.Open = true;			// LeftPane open/closed
}

/**
 * Initialize the splitter by setting the events.
 * @param {event} e
 * @param {object|string} HandleEl
 * @param {object|string} LeftEl
 * @param {object|string} RightEl
 */
Splitter.prototype.init = function(HandleEl, LeftEl, RightEl) {
	var Self = this;	// closure for this
	this.EventRef1 =  function(e) {	Self.Drag(e); }
	this.Handle = typeof HandleEl === 'string' ? d.getElementById(HandleEl) : HandleEl;
	if (typeof LeftEl === 'string') {
		this.LeftEl = d.getElementById(LeftEl);
	}
	else {
		this.LeftEl = LeftEl;
	}
	if (typeof RightEl === 'string') {
		this.RightEl = d.getElementById(RightEl);
	}
	else {
		this.RightEl = RightEl
	}
	this.Handle.addEventListener('mousedown', function(e){
		e.preventDefault();
		Self.LastX = e.clientX;
		window.addEventListener('mousemove', Self.EventRef1, false);
	}, false);
	window.addEventListener('mouseup', function(){ Self.EndDrag(); }, false);
	window.addEventListener('dblclick', function(){ Self.Close(); }, false);
	this.OrigX = d.defaultView.getComputedStyle(this.LeftEl, '').getPropertyValue('width');
}

/**
 * Sets the width and position of the elements when dragging.
 * @param {event} e
 */
Splitter.prototype.Drag = function(e) {		
	var DifX = e.clientX - this.LastX;
	var PosX = d.defaultView.getComputedStyle(this.RightEl, '').getPropertyValue('left');
	var NewX = parseInt(PosX) + DifX;
	this.LeftEl.style.width = NewX + "px";
	this.RightEl.style.left = (NewX + parseInt(d.defaultView.getComputedStyle(this	.RightEl, '').getPropertyValue('padding-left'))) + "px";
	this.LastX = e.clientX;
}

/**
 * Terminates the dragging on mouseup and removes the listener.
 */
Splitter.prototype.EndDrag = function() {
	window.removeEventListener('mousemove', this.EventRef1, false);
}

/**
 * Closes/opens the pane containing the handle.
 */
Splitter.prototype.Close = function() {
	if (this.Open) {
		var X = d.defaultView.getComputedStyle(this.Handle, '').getPropertyValue('width');
		this.Open = false;
	}
	else {
		var X = this.OrigX;		
	}
	this.LeftEl.style.width = X;
	this.RightEl.style.left = X;
}
