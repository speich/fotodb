/**
 * Class to create a file explorer.
 *
 */
 
/**
 * @constructor
 * @param {object} ParEl parent HTMLElement to append data to
 * @param {string} PHPUrl root directory, not of website but of PHP filebrowser file
 * @param {string} Type type of explorers
 */
function Explorer(ParEl, PHPUrl, Type) {
	this.ParEl = ParEl;
	this.Type = Type;		// file or image explorer
	this.PHPUrl = PHPUrl;
	this.Ajax = new XmlHttp();
	this.Dir = '';		// current open directory path
	this.Filter = 0;	// 0 || 1 = show only not done, 2 = show only done, 3 show both 
}

/**
 * Load files in directory.
 * 
 * The dir argument is the path starting from webroot.
 * 
 * @param {string} Dir directory path
 */
Explorer.prototype.LoadFiles = function() {
	if (this.Ajax) {
		var Query = this.PHPUrl;
		if (arguments[0]) {
			this.Dir = arguments[0];
			Query += '?Dir=' + this.Dir + '&';
		}
		else {
			Query += '?';
		}
		Query += 'Filter=' + this.Filter + '&Type=' + this.Type;
		if (this.LoadingFnc) { this.LoadingFnc(); }
		this.Ajax.SetDoneFnc(this, this.DisplayFiles, this.Ajax);
		this.Ajax.LoadData(Query);
	}
}

Explorer.prototype.DisplayFiles = function() {
	// Ajax DoneFnc: called async by method LoadFiles
	var Self = this;
	this.ParEl.innerHTML = this.Ajax.Result;
	if (this.Type == 'File') {
		var arrTr = this.ParEl.getElementsByTagName('tr');
		var i = arrTr.length-1;
		for (i; i >= 0; i--) {
			arrTr[i].style.cursor = 'pointer';
			if (/Dir/.test(arrTr[i].getAttribute('class'))) {
				arrTr[i].addEventListener('click', function(e) { Self.ChangeDir(e); }, false);
			}
		}
	}
	if (this.DoneFnc) { this.DoneFnc(); }
}

Explorer.prototype.ChangeDir = function(e) {
	var Dir = e.currentTarget.getElementsByTagName('a').item(0).getAttribute('href');
	e.preventDefault();// do not follow link
	this.LoadFiles(Dir);
}

Explorer.prototype.SetDoneFnc = function(Obj, Fnc, Args) {	
	// callback function to be executed when finished loading files, e.g called by method DisplayFiles
	this.DoneFnc = function() {
		if (!Fnc) { return false; }
		if (!Args) { Fnc.call(Obj); }
		else if (typeof Args == 'object' && Args.constructor == Array) {
			Fnc.apply(Obj, Args);
		}
		else { Fnc.call(Obj, Args); }
	}	
}

Explorer.prototype.SetLoadingFnc = function(Obj, Fnc, Args) {	
	// callback function to be executed before loading files, e.g called by method LoadFiles
	this.LoadingFnc = function() {
		if (!Fnc) { return false; }
		if (!Args) { Fnc.call(Obj); }
		else if (typeof Args == 'object' && Args.constructor == Array) {
			Fnc.apply(Obj, Args);
		}
		else { Fnc.call(Obj, Args); }
	}	
}

Explorer.prototype.HighlightRow = function(Row) {
	// remove previous highlighted tr
	var arrNode = this.ParEl.getElementsByTagName('tr');
	var j = arrNode.length - 1;
	for (; j > -1; j--) { if (arrNode[j].style) { arrNode[j].style.backgroundColor = 'inherit'; }}
	Row.style.backgroundColor = 'grey';
}

