define([
	'dojo/_base/declare',
	'dojo/request/xhr',
	'../classes/class_tools.js',
	'../classes/class_xmlhttp.js'
], function(declare, xhr) {

	// private vars
	var d = document,
		PHPDbFncUrl = require.toUrl('fotodb') + '/../dbprivate/library/dbfunctions.php',
		CurImgId = null,
		El = null,	// form element reference. Can only be set after page load.
		Tool = new Tools(),
		gmaps = null,

		/**
		 * Shortcut for document.getElementById
		 * @param {String} id
		 */
		byId = function(id) {
			return document.getElementById(id);
		};

	return declare(null, {

		fotoDb: null,
		arrFld: null,

		constructor: function (arrFld, fotoDb) {
			this.arrFld = arrFld;	// holds all form names, whose values should be saved to database
			this.fotoDb = fotoDb;

			gmaps = google.maps;
		},

		GetCurImgId: function() {
			if (CurImgId) {	return CurImgId; }
			else { return false; }
		},

		SetCurImgId: function(Id) { CurImgId = Id; },

		/**
		 * Return form element reference.
		 * @return object HtmlFormElement
		 */
		GetFormEl: function () {
			if (El) {
				return El;
			}
			else {
				return false;
			}
		},

		/**
		 * Set form element reference.
		 * @param {string} FormName
		 */
		SetFormEl: function (FormName) {
			El = byId(FormName);
		},

		/**
		 * Fill in form data if users wants to edit image.
		 *
		 * This method takes a Xml DOM object as input and traverses every node
		 * and takes its attribute name and attribute value to set a form field with
		 * the correspondent name and value.
		 *
		 * @param {object} Xml
		 */
		Fill: function (Xml) {
			if (!Xml) {
				alert('Please copy first.');
				return false;
			}

			var self = this,
				lat, lng, point,
				mapTool = this.fotoDb.mapTool;

			this.fotoDb.ClearLocation();
			this.fotoDb.ClearKeyword();
			this.fotoDb.ClearSpecies();

			// create flat array from element and attribute nodes to traverse xml data
			// not recursive. Tree only two levels deep
			var List = Xml.getElementsByTagName('HtmlFormData').item(0).childNodes;
			var i = List.length - 1;
			for (; i > -1; i--) {
				if (List[i].nodeType != Node.ELEMENT_NODE) {
					continue;
				}	// skip non element nodes
				var Nodes = Tool.ConvertNodeListToArray(List[i].attributes);	// convert attributes of current node to array
				if (List[i].childNodes.length > 0) {
					Nodes = Nodes.concat(Tool.ConvertNodeListToArray(List[i].childNodes));	// append child nodes of to array
				}
				var j = Nodes.length - 1;
				for (; j > -1; j--) {
					var el;

					if (Nodes[j].nodeName === 'ImgLat' || Nodes[j].nodeName === 'ImgLng') {
						// do not overwrite gps data from exif
						el = document.getElementById(Nodes[j].nodeName);
						if (el && el.overwrite === false) {
							el.overwrite = true;
							continue;
						}
					}

					// Species module
					if (Nodes[j].nodeName == 'ScientificName') {
						this.fotoDb.SetSpecies(
						Nodes[j].getAttribute('Id'),
						Tool.DecodeHtml(Nodes[j].getAttribute('NameDe')),
						Tool.DecodeHtml(Nodes[j].getAttribute('NameEn')),
						Tool.DecodeHtml(Nodes[j].getAttribute('NameLa')),
						Tool.DecodeHtml(Nodes[j].getAttribute('SexId')),
						Tool.DecodeHtml(Nodes[j].getAttribute('SexText'))
						);
					}
					// locations part of form
					else if (Nodes[j].nodeName == 'Location') {
						this.fotoDb.SetLocation(Nodes[j].getAttribute('Id'), Tool.DecodeHtml(Nodes[j].getAttribute('Name')));
					}
					else if (Nodes[j].nodeName == 'Keyword') {
						this.fotoDb.SetKeyword(Nodes[j].getAttribute('Id'), Tool.DecodeHtml(Nodes[j].getAttribute('Name')));
					}
					else {
						Set(Nodes[j]);
					}
				}
			}
			// google maps module
			// display image on the map
			if (mapTool && mapTool.map) {
				mapTool.clearMarkers();

				lat = byId('ImgLat').value;
				lng = byId('ImgLng').value;

				if (lat !== '' && lng !== '') {
					mapTool.addMarker({
						lat: lat,
						lng: lng,
						img: byId('ImgPreview').src
					}, 'image');

					point = new gmaps.LatLng(lat, lng);
					mapTool.map.setCenter(point);

					if (byId('Locations').getElementsByTagName('div').length === 0) {
						mapTool.reverseGeocode(new gmaps.LatLng(lat, lng)).then(function(result) {
							self.updateLocation(result);
						});
					}
				}
			}
			// make all select fields reflect new DB content after inserting new content
			this.UpdateSelectFields();

			/**
			 * Set Html form fields or elements.
			 *
			 * @param {NodeList} arrData
			 */
			function Set(N) {
				var Name = N.nodeName;
				if (N.nodeType == Node.ATTRIBUTE_NODE) {
					var Value = Tool.DecodeHtml(N.nodeValue);
				}
				else {
					var Value = N.getAttribute('Id');
				}
				var Fld = byId(Name);
				if (Fld) {
					var Tag = Fld.nodeName;
					switch (Tag) {
						case 'INPUT':
							var Type = Fld.getAttribute('type').toUpperCase();
							if (Type == 'CHECKBOX') {
								if (Value == 1) {
									Fld.checked = true;
								}
								else {
									Fld.checked = false;
								}
							}
							else {
								Fld.value = Tool.DecodeHtml(Value);
							}
							break;
						case 'TEXTAREA':
							Fld.value = Tool.DecodeHtml(Value);
							break;
						case 'SELECT':
							var j = Fld.options.length - 1;
							for (; j > -1; j--) {
								if (Fld.options[j].value == Value) {
									Fld.options[j].selected = true;
								}
							}
							break;
					}
				}
			}
		},


		/**
		 * Setup form element properties.
		 *
		 * @param {string} FormName
		 */
		init: function (FormName) {
			this.SetFormEl(FormName);
			// set form field properties
			var i = this.arrFld.length - 1;
			for (; i > -1; i--) {
				byId(this.arrFld[i][0]).XmlInclude = true;							// include form field in Xml data ?
				byId(this.arrFld[i][0]).XmlElName = this.arrFld[i][1];	// name of xml element this element is going to be an attribute of
			}
		},

		/**
		 * Create Xml document from form input.
		 *
		 * Each HTMLElementAttribute XmlElName is used as xml element name and corresponds with database table name. HTMLElement
		 * id is used as attribute name, form value as attribute value.
		 *
		 * @return {object} Xml form data
		 */
		SaveXml: function () {
			// convert html (form) data to xml data
			var Xml = d.implementation.createDocument("", "", null);
			var Root = Xml.createElement('HtmlFormData');
			var Val = '';
			Root.setAttribute('xml:lang', 'de-CH');
			Root.setAttribute('encoding', 'UTF-8');
			Root = Xml.appendChild(Root);
			var El = this.GetFormEl().getElementsByTagName('*');	// instead of Frm = this.GetFormEl();Elm[i] to catch also non form elements such as div tags (SpeciesModule)
			var i = El.length - 1;
			for (; i > -1; i--) {
				if (El[i].XmlInclude) {
					var Node = Xml.getElementsByTagName(El[i].XmlElName);
					if (Node.length == 0) { // create element first if it doesn' exist
						Node = Xml.createElement(El[i].XmlElName);
						Node.setAttribute('Id', this.GetCurImgId());
						Node = Root.appendChild(Node);
					}
					else {
						Node = Node.item(0);
					}
					switch (El[i].nodeName.toUpperCase()) {
						case 'INPUT':
							if (El[i].getAttribute('type').toLowerCase() == 'checkbox') {
								if (El[i].checked) {
									El[i].value = 1;
								}	// if checkbox gets unchecked, make sure this gets saved to...
								else {
									El[i].value = 0;
								}
							}
							Val = Tool.EncodeHtml(El[i].value);
							Node.setAttribute(El[i].id, Val);
							break;
						case 'SELECT':
							if (El[i].multiple) {
								var j = El[i].options.length - 1;
								for (; j > -1; j--) {
									if (El[i].options[j].selected) {
										var Child = Node.appendChild(Xml.createElement(El[i].id));
										Child.setAttribute('Id', El[i].options[j].value);
									}
								}
							}
							else {
								Val = Tool.EncodeHtml(El[i].value);
								Node.setAttribute(El[i].id, Val);
							}
							break;
						case 'DIV':
							// special Species module
							if (El[i].parentNode.id == 'Species') {
								var Child = Node.appendChild(Xml.createElement('ScientificName'));
								Child.setAttribute('Id', El[i].id);
								// TODO: improve code
								// ugly, if html is changed then it might not be at 4th node.
								// and what about white space ?
								Val = Tool.EncodeHtml(El[i].childNodes[0].firstChild.data);
								Child.setAttribute('NameDe', Val);
								Val = Tool.EncodeHtml(El[i].childNodes[1].firstChild.data);
								Child.setAttribute('NameEn', Val);
								Val = Tool.EncodeHtml(El[i].childNodes[2].firstChild.data);
								Child.setAttribute('NameLa', Val);
								Val = Tool.EncodeHtml(El[i].childNodes[3].id);
								Child.setAttribute('SexId', Val);
								Val = Tool.EncodeHtml(El[i].childNodes[3].firstChild.data);
								Child.setAttribute('SexText', Val);
							}
							// location module
							else if (El[i].parentNode.id == 'Locations') {
								var Country = byId('CountryId');
								if (Country[Country.selectedIndex].value > 0) {
									Node.setAttribute('CountryId', Country[Country.selectedIndex].value);
								}
								var Child = Node.appendChild(Xml.createElement('Location'));
								Child.setAttribute('Id', El[i].id);
								Val = Tool.EncodeHtml(El[i].getElementsByTagName('div')[0].firstChild.nodeValue);
								Child.setAttribute('Name', Val);
							}
							// keywords
							else if (El[i].parentNode.id == 'Keywords') {
								var Child = Node.appendChild(Xml.createElement('Keyword'));
								Child.setAttribute('Id', El[i].id);
								Val = Tool.EncodeHtml(El[i].childNodes[1].nodeValue);
								Child.setAttribute('Name', Val);
							}
							break;
						default:
							Val = Tool.EncodeHtml(El[i].value);
							Node.setAttribute(El[i].id, Val);
							break;
					}
				}
			}
			return Xml;
		},

		/**
		 * Post form input to PHP and save it in SQLite database.
		 */
		SaveAll: function () {
			if (!this.fotoDb.GetCurImgId()) {
				alert('Select an image first.');
				return false;
			}
			// TODO: check if obligatory fields are filed out
			if (!this.CheckFlds()) {
				return false;
			}
			var Serializer = new XMLSerializer(); 	// ATTENTION: this is mozilla only code
			var XmlData = this.SaveXml();
			var XmlData = Serializer.serializeToString(XmlData);
			XmlData = window.encodeURIComponent(XmlData);
			var Ajax = new XmlHttp();
			Ajax.Method = 'POST';
			Ajax.Charset = 'UTF-8';
			Ajax.Query = 'Fnc=UpdateAll&XmlData=' + XmlData;
			Ajax.SetLoadingFnc(this, this.DisableFields);
			Ajax.SetDoneFnc(this, function () {
				this.EnableFields();
				this.UpdateSelectFields();	// after saving image data some select fields should be updated
			});
			Ajax.LoadData(PHPDbFncUrl);
		},

		/**
		 * Check if all mandatory form fields are filled out.
		 */
		CheckFlds: function () {
			// only check if country is selected if at least a location is set
			if (byId('Locations').getElementsByTagName('div').length > 0) {
				var El = byId('CountryId');
				if (El.value == '') {
					alert('Please select a country for your locations');
					El.focus();
					return false;
				}
			}
			return true;
		},

		/**
		 * Set all from fields to disabled.
		 */
		DisableFields: function () {
			var El = this.GetFormEl();
			var i = El.length - 1;
			for (; i > -1; i--) {
				El[i].disabled = true;
			}
		},

		/**
		 * Set all form field to enabled.
		 */
		EnableFields: function () {
			var El = this.GetFormEl();
			var i = El.length - 1;
			for (; i > -1; i--) {
				El[i].disabled = false;
			}
		},

		/**
		 * Call this method to reload certain select field content.
		 */
		UpdateSelectFields: function () {
			this.fotoDb.ReloadLocation();	// after adding new location data update list of locations to reflect that
		},

		updateLocation: function(data) {
			// fill locations part of form
			var self = this,
			nodes = data.getElementsByTagName('GeoName'),
			i = 0, len = nodes.length,
			opt, target,
			el = byId('CountryId');

			if (!data) {
				alert('Reverse geocoding failed');
				return;
			}

			if (len > 0) {
				for (i = 0; i < len; i++) {
					var name = data.getElementsByTagName('GeoName')[i].firstChild.nodeValue;
					self.fotoDb.SetLocation('', name);	// id is given after insert into database
				}
			}

			// set HTMLSelectElement CountryId selected
			for (i = 0, len = el.options.length; i < len; i++) {
				var countryName = data.getElementsByTagName('CountryName').item(0).firstChild.data;

				opt = el.options[i];
				if (opt.text === countryName) {
					opt.selected = true;
					break;
				}
			}

			target = PHPDbFncUrl + '?Fnc=FldLoadData&FldName=Location&CountryId=' + opt.value;
			xhr.post(target,  {
				handleAs: 'text'
			}).then(function(result) {
				byId('Location').innerHTML = result;
			});
		}

	});
});