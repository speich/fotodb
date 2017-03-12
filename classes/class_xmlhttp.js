// Simple XmlHttp class with two callback functions
// Created for www.lfi.ch by Simon Speich
// v 1.0, 24.11.2006

// 1. callback while loading
// 2. callback done loading


function XmlHttp() {
	var Self = this;							// create closure for this: make sure this points to the right object (variable scope)
	this.Ajax = null;							// request object not yet created
	this.Type = "Async";					// or Sync
	this.Method = "GET";					// or POST
	this.Doctype = "Txt";					// type of response. If set to Xml and method POST ($_GET und $_POST vars in PHP not available)
	this.Result = "";							// stores result of query
	this.Query = false;						// data to send to server if POST
	this.Repeat = false;					// call LoadingFnc repeatedly until done loading
	this.Charset = 'iso-8859';		// charset for POST method
	this.ImgLoading = new Image();// animated image to display while loading
	this.ImgLoading.src = '../dbprivate/layout/images/ajax_loading.gif';
	var CallFnc = true;						// call loading function only once
	
	try {
		this.Ajax = new XMLHttpRequest();
		this.Ajax.onreadystatechange = function () {
			switch(Self.Ajax.readyState) {
				case 1:													// 1 start loading, called repeatedly until loaded
					if (CallFnc && Self.LoadingFnc) {
						Self.LoadingFnc();
						if (!Self.Repeat) { CallFnc = false; }	// call only once
					}
					break;
				case 4:													// 4 done loading
					if (Self.Ajax.status == 200) {	// http response ok
						Self.Result = (Self.Doctype == 'Xml' ? Self.Ajax.responseXML : Self.Ajax.responseText);
						if (Self.DoneFnc) { Self.DoneFnc(); }
					}
					else { 
						alert('Problem with server: ' + Self.Ajax.status);
					}
					break;
			}
		}
	}
	catch(e) { return false; }
}

/**
 * Callback function to be executed while loading.
 * 
 * If the class property Repeat is set to true. This function is called repeatedly.
 */
XmlHttp.prototype.SetLoadingFnc = function(Obj, Fnc, Args) {	
	// 
	this.LoadingFnc = function() {
		if (!Fnc) { return false; }	
		if (!Args) { Fnc.call(Obj); }
		else if (typeof Args == 'object' && Args.constructor == Array) {
			Fnc.apply(Obj, Args);
		}
		else { Fnc.call(Obj, Args); }
	}	
}

/**
 * Callback function to be executed when finished loading.
 * 
 * The argument Obj is the object the this keyword of the function Fnc should point to.
 * 
 * @param {object} this
 * @param {function} function to be executed
 * @param {string|array} optional arguments of function to be executed
 */
XmlHttp.prototype.SetDoneFnc = function(Obj, Fnc, Args) {	
	// 
	this.DoneFnc = function() {
		if (!Fnc) { return false; }
		if (!Args) { Fnc.call(Obj); }
		else if (typeof Args == 'object' && Args.constructor == Array) {
			Fnc.apply(Obj, Args);
		}
		else { Fnc.call(Obj, Args); }
	}	
}

XmlHttp.prototype.LoadData = function(Url) {
	// load document at url
	this.Ajax.open(this.Method, Url, this.Type == 'Async' ? true : false);
	if (this.Method == 'GET') {
		this.Ajax.send(null);
	}
	else {
		this.Ajax.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded', this.Charset);
		this.Ajax.send(this.Query);
	}
}
