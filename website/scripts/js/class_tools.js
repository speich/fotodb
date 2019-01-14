/**
 * Helper class with a collection off useful methods.
 * 
 * the whitespace methods are taken from http://developer.mozilla.org/en/docs/Whitespace_in_the_DOM
 */
 


/**
 * @constructor
 */
function Tools() {
	// whitespace
  this.RegExpr = /[^\t\n\r ]/;
  /*	 Whitespace is defined as one of the characters
	 "\t" TAB \u0009
	 "\n" LF  \u000A
	 "\r" CR  \u000D
	 " "  SPC \u0020
	 This does not use Javascript's "\s" because that includes non-breaking
	 spaces (and also some other characters).
	*/
}

/**
 * Autonumber for objects id (static class variable)
 */
Tools.ObjIndex = 0; 

/**
 * Array to keep track of objects (static class variable)
 */
Tools.arrGlobalObj = new Array();

/**
 * Make the array where the objects are stored available to the object instance.
 * @return {array} Array
 */
Tools.prototype.GetGlobalObjArray = function() { return Tools.arrGlobalObj; }

/**
 * Store an object reference in a global array.
 * 
 * By storing an object in the obj array you can keep track of it,
 * for example check if object was already used/set.
 * 
 * @param {object} Obj object reference
 * @param {mixed} [Id] Id
 * @return integer
 */
Tools.prototype.AddObj = function(Obj) {
	if (arguments[1]) {
		var Id = arguments[1];
	}
	else {
		var Id = Tools.ObjIndex;
	}
	var Len = Tools.arrGlobalObj.length;
	// The referenced object might have its own property Id
	// => create new object and attach ObjRef as a property 
	// and set your own property Id.
	Tools.arrGlobalObj[Len] = new Object();
	Tools.arrGlobalObj[Len].Obj = Obj;
	Tools.arrGlobalObj[Len].Id = Id;
	Tools.ObjIndex++;
	return Id;
}


/**
 * Retrieve the object from the global storage array.
 * 
 * Returns null if object not found.
 * 
 * @param {integer} Id
 * @return object|null
 */
Tools.prototype.GetObjById = function(Id) {
	var i = Tools.arrGlobalObj.length - 1;
	for (; i > -1; i--) {
		if (Tools.arrGlobalObj[i].Id == Id) {
			return Tools.arrGlobalObj[i].Obj;
		}
	}
	return null;
}

/**
 * Remove object from the global storage array.
 * 
 * @param {integer} Id
 */
Tools.prototype.RemoveObjById = function(Id) {
	var i = Tools.arrGlobalObj.length - 1;
	for (; i > -1; i--) {
		// go through whole array, there might be more than one storage per id
		if (Tools.arrGlobalObj[i].Id == Id) {
			Tools.arrGlobalObj.splice(i, 1);
		}
	}
}

/**
 * Remove object from the global storage array.
 * 
 * @param {object} Obj object reference
 */
Tools.prototype.RemoveObj = function(Obj) {
	var i = Tools.arrGlobalObj.length - 1;
	for (; i > -1; i--) {
		// check if temp object of current image already exists
		// go through whole array, there might be more than one storage per object
		if (Tools.arrGlobalObj[i].Obj == Obj) {
			Tools.arrGlobalObj.splice(i, 1);
		}
	}
}


/**
 * Check if an object reference was already stored in the object array.
 * 
 * Returns null if object not in array otherwise its id.
 * 
 * @param {object} Obj
 * @return integer|null
 */
Tools.prototype.CheckObj = function(Obj) {
	var i = Tools.arrGlobalObj.length - 1;
	for (; i > -1; i--) {
		if (Tools.arrGlobalObj[i].Obj == Obj) {
			return Tools.arrGlobalObj[i].Id;
		}
	}
	return null;
}

/**
 * Check if an object reference was already stored in the object array by using an object as the index.
 * 
 * Returns null if object not in array otherwise its IndexObject.
 * 
 * @param {object} Id
 * @return object|null
 */
Tools.prototype.CheckObjById = function(Id) {
	var i = Tools.arrGlobalObj.length - 1;
	for (; i > -1; i--) {
		if (Tools.arrGlobalObj[i].Id == Id) {
			return Tools.arrGlobalObj[i].Obj;
		}
	}
	return null;
}

/**
 * Calculate an element's position (x and y-coordinate).
 * Taken from http://www.quirksmode.org/js/findpos.html.
 * 
 * @param {object} HTMLElement
 * @return {array} x and y coordinates 
 */
Tools.prototype.GetPos = function(El) {
	var Left = Top = 0;
	if (El.offsetParent) {
		Left = El.offsetLeft;
		Top = El.offsetTop;
		while (El = El.offsetParent) {
			Left += El.offsetLeft;
			Top += El.offsetTop;
		}
	}
	return [Left,Top];
}

Tools.prototype.CheckWhiteSpace = function(Node) {
	// Determine whether a node's text content is entirely whitespace
	// Return true if all of str is whitespace, otherwise false.
  return !this.RegExpr.test(Node.nodeValue);
}

Tools.prototype.IgnoreNode = function(Node) {
	// Return true if the node is a comment node or a text node that is all whitespace
	// otherwise return false.
  return (Node.nodeType == 8) ||														// A comment node
         (Node.nodeType == 3 && this.CheckWhiteSpace(Node)); // A text node and all WS
}

/**
 * Converts a NamedNodeMap (node list) to an array
 * 
 * @param object NodeList NamedNodeMap
 * @return array
 */
Tools.prototype.ConvertNodeListToArray = function(NodeList) {
	var arr = [];
	var i = 0;
	var Len = NodeList.length;
	for (;i < Len; i++) {
		arr[i] = NodeList.item(i);
	}
	return arr;
}

//Tools.prototype.FindElementNode



/**
 * Determine whether a node's text content is entirely whitespace.
 *
 * @param nod  A node implementing the |CharacterData| interface (i.e.,
 *             a |Text|, |Comment|, or |CDATASection| node
 * @return     True if all of the text content of |nod| is whitespace,
 *             otherwise false.
 */
Tools.prototype.IsAllWhiteSpace = function(Node) {
  // Use ECMA-262 Edition 3 String and RegExp features
  return !(/[^\t\n\r ]/.test(Node.data));
}

/**
 * Determine if a node should be ignored by the iterator functions.
 *
 * @param Node  An object implementing the DOM1 |Node| interface.
 * @return     true if the node is:
 *                1) A |Text| node that is all whitespace
 *                2) A |Comment| node
 *             and otherwise false.
 */
Tools.prototype.Ignore = function(Node) {
  return (Node.nodeType == 8) || // A comment node
         ((Node.nodeType == 3) && this.IsAllWhiteSpace(Node)); // a text node, all ws
}

/**
 * Version of |previousSibling| that skips nodes that are entirely
 * whitespace or comments.  (Normally |previousSibling| is a property
 * of all DOM nodes that gives the sibling node, the node that is
 * a child of the same parent, that occurs immediately before the
 * reference node.)
 *
 * @param Sib The reference node.
 * @return    Either:
 *            1) The closest previous sibling to |sib| that is not
 *               ignorable according to |Ignore|, or
 *            2) null if no such node exists.
 */
Tools.prototype.PreviousNode = function(Sib) {
  while ((Sib = Sib.previousSibling)) {
    if (!this.Ignore(Sib)) { return Sib; }
  }
  return null;
}

/**
 * Version of |nextSibling| that skips nodes that are entirely whitespace or comments.
 *
 * Returns either:
 * 1) The closest next sibling to |Sib| that is not ignorable according to |Ignore|, or
 * 2) null if no such node exists.
 * 
 * @param object The reference node.
 * @return object|null
 * 
 */
Tools.prototype.NextNode = function(Sib) {
  while ((Sib = Sib.nextSibling)) {
    if (!this.Ignore(Sib)) { return Sib; }
  }
  return null;
}

/**
 * Version of |lastChild| that skips nodes that are entirely
 * whitespace or comments.  (Normally |lastChild| is a property
 * of all DOM nodes that gives the last of the nodes contained
 * directly in the reference node.)
 *
 * @param Par The reference node.
 * @return    Either:
 *              1) The last child of |Sib| that is not
 *                 ignorable according to |Ignore|, or
 *              2) null if no such node exists.
 */
Tools.prototype.LastChild = function(Par) {
  var res = Par.lastChild;
  while (res) {
    if (!this.Ignore(res)) { return res; }
    res = res.previousSibling;
  }
  return null;
}

/**
 * Version of |firstChild| that skips nodes that are entirely
 * whitespace and comments.
 *
 * @param Par The reference node.
 * @return     Either:
 *               1) The first child of |Sib| that is not
 *                  ignorable according to |Ignore|, or
 *               2) null if no such node exists.
 */
Tools.prototype.FirstChild = function(Par) {
  var res = Par.firstChild;
  while (res) {
    if (!Ignore(res)) { return res; }
    res = res.nextSibling;
  }
  return null;
}

/**
 * Version of |data| that doesn't include whitespace at the beginning
 * and end and normalizes all whitespace to a single space.  (Normally
 * |data| is a property of text nodes that gives the text of the node.)
 *
 * @param string Txt The text node whose data should be returned
 * @return string A string giving the contents of the text node with whitespace collapsed.
 */
Tools.prototype.Data = function(Txt) {
  var data = Txt.data;
  // Use ECMA-262 Edition 3 String and RegExp features
  data = data.replace(/[\t\n\r ]+/g, " ");
  if (data.charAt(0) == " ")
    data = data.substring(1, data.length);
  if (data.charAt(data.length - 1) == " ")
    data = data.substring(0, data.length - 1);
  return data;
}

/**
 * Encodes a string to be used as XML character data.
 * Translates the characters <,>,&,",' into their entities.
 * @param {string} Txt
 * @return string
 */
Tools.prototype.EncodeHtml = function(Txt) {
	Txt = Txt.replace('&', '&amp;');
	Txt = Txt.replace("'", '&#039;');
	Txt = Txt.replace('"', '&quot;');
	Txt = Txt.replace('<', '&lt;');
	Txt = Txt.replace('>', '&gt;');
	return Txt;
}

/**
 * Decodes a string encoded with EncodeHtml.
 * Translates the enties &amp;, &#039;, &quot, &lt;, &gt; into their characters.
 * @param {string} Txt
 * @return string
 */
Tools.prototype.DecodeHtml = function(Txt) {
	Txt = Txt.replace('&amp;', '&');
	Txt = Txt.replace('&#039;', "'");
	Txt = Txt.replace('&quot;', '"');
	Txt = Txt.replace('&lt;', '<');
	Txt = Txt.replace('&gt;', '>');
	return Txt;
}

