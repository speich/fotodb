define([
	'dojo/_base/declare',
	'dojo/_base/lang',
	'dojo/query',
	'dojo/keys',
	'dojo/dom',
	'dojo/request/xhr',
	'dojo/on',
	'dojo/dom-style',
	'dojo/_base/array',
	'dojo/store/JsonRest',
	"dojox/data/QueryReadStore",
	"dijit/form/ComboBox",
	"dijit/form/FilteringSelect",
	"dijit/layout/BorderContainer",
	"dijit/layout/ContentPane",
	"dijit/layout/TabContainer",
	'fotodb/DbForm',
	'fotodb/GoogleMap',
	'../scripts/js/class_tools.js',
	'../scripts/js/class_xmlhttp.js',
	'../scripts/js/class_explorer.js',
	'../scripts/js/class_fieldfilter.js'
], function(declare, lang, query, keys, dom, xhr, on, domStyle, array, JsonRest, QueryReadStore, ComboBox, FilteringSelect, BorderContainer, ContentPane, TabContainer, DbForm, GoogleMap) {

	var CurImgId = null,
		d = document,
		PHPDbFncUrl = require.toUrl('fotodb') + '/../php/controller/dbfunctions.php',
		PHPExifUrl = require.toUrl('fotodb') + '/../php/controller/service-exif.php',
		PHPExplorerUrl = require.toUrl('fotodb') + '/../php/controller/explorer.php',		// url for PHP explorer script
		Tool = new Tools(),

		/**
		 * Shortcut for document.getElementById
		 * @param {String} id
		 */
		byId = function(id) {
			return document.getElementById(id);
		};

	return declare(null, {

		curPromise: null,

		GetCurImgId: function() {
			return CurImgId ? CurImgId: false;
		},

		/**
		 * This method sets the private member CurImgId and returns it.
		 * @member DbFoto
		 * @param integer Id image id
		 */
		SetCurImgId: function(Id) {
			CurImgId = Id;
		},

		SetupExplorer: function() {
			var self = this;
			this.Explorer = new Explorer(byId('ImgExplCont'), PHPExplorerUrl, 'File');

			// file list filters
			this.Explorer.Filter = 3;	// show both
			var F1 = byId('FEFilterNotDone');
			var F2 = byId('FEFilterDone');
			F1.addEventListener('click', function() {
				F1.checked ? self.Explorer.Filter += 1: self.Explorer.Filter -= 1;
				if (!F1.checked && !F2.checked) {
					F1.checked = true;
					self.Explorer.Filter += 1;
				}
				self.Explorer.LoadFiles(self.Explorer.Dir);
			}, false);
			F2.addEventListener('click', function() {
				F2.checked ? self.Explorer.Filter += 2: self.Explorer.Filter -= 2;
				if (!F1.checked && !F2.checked) {
					F1.checked = true;
					self.Explorer.Filter += 1;
				}
				self.Explorer.LoadFiles(self.Explorer.Dir);
			}, false);
			F1.checked = 'checked';
			F2.checked = 'checked';
			byId('FEType').addEventListener('change', function() {
				self.Explorer.Type = this.value;
				self.Explorer.LoadFiles(self.Explorer.Dir);
			}, false);

			//this.Explorer.SetLoadingFnc(this.Explorer)

			// ajax done loading selected image or directory
			this.Explorer.SetDoneFnc(this.Explorer, function() {

				if (this.Type === 'File') {
					var img, i, arrTr = self.Explorer.ParEl.getElementsByTagName('tr');

					i = arrTr.length - 1;
					for (; i > -1; i--) {
						// setup image click event
						if (!arrTr[i].classList.contains('dir')) {
							arrTr[i].addEventListener('click', function() {
								img = this.getElementsByTagName('img').item(0);
								// row highlighting, remove previously selected rows
								self.Explorer.HighlightRow(this);
								// display exif data
								self.DisplExifData(img, byId('PaneLeftExifContent'));
								// update/edit/insert image
								self.SetImg(img);
							}, false);
						}
					}

					self.queryAllImagesSettled('.FileExplorer img')
						.then(() => {
							let id, img, oldHash = location.hash;

							// browser scroll to hash on load because images are not loaded yet
							// but a specific hash can only be set once, so reset if fist, check if all images loaded (because setting it
							// to early will scroll to wrong height because of every thumbnail being loaded changes the height of the explorer.
							//      window.location.hash = oldHash;
							id = oldHash.slice(1);
							if (id !== '' && byId(id)) {  // image with id might no longer exist in database
								img = byId(id);
								img.scrollIntoView({behavior: 'smooth', block: 'center'});
								img.parentNode.parentNode.classList.add('selected');
								self.SetImg(img);
							}
						});
				}
			});
			this.Explorer.LoadFiles();
		},

		/**
		 *
		 * @param {DOMString} selectors css selectors to match
		 * @returns {Promise<any[]>}
		 */
		queryAllImagesSettled: function(selectors) {
			let images = document.querySelectorAll(selectors),
				promises = [];

			images.forEach(function(img) {
				let promise;

				if (img.complete) {
					// in case image is already loaded before we add the onload event
					promise = Promise.resolve();
				}
				else {
					promise = new Promise((resolve) => {
						img.addEventListener('load', resolve);
						img.addEventListener('error', resolve);
					});
				}
				promises.push(promise);

			});

			return Promise.all(promises);
		},

		/**
		 * Handles communication between js and database when an image is clicked in the image/file explorer.
		 *
		 * @param {object} img HTMLImageElement reference
		 * @return {Promise}
		 */
		SetImg: function(img) {
			let request;

			this.Frm.GetFormEl().reset();
			// disable form input on until insert image has completed. Otherwise CurImgId not correctly set for form
			this.Frm.DisableFields();
			img.parentNode.parentNode.style.opacity = 0.3;	// set visual clue that image data is being set

			this.placeImage(img);

			// if img has id then edit else insert
			if (img.id) {
				request = this.editImage(img).then(xml => {
					let id = this.getImgIdXml(xml);

					// make sure the returned id is the same as the selected image
					if (img.id === id) {
						return xml;
					}
					else {
						// TODO: automatically select the image in Explorer with the correct id
						throw new Error('wrong image');
					}
				}).catch(console.log.bind(console));
			}
			else {
				request = this.insertImage(img).then(xml => {
					img.id = this.getImgIdXml(xml);

					return xml;
				}).catch(console.log.bind(console));
			}

			return request.then(xml => {
				this.SetCurImgId(img.id);
				this.Frm.SetCurImgId(img.id);
				this.Frm.Fill(xml);
				// mark image if its in the database
				byId(img.id).parentNode.setAttribute('class', 'MarkDone');
				// set hash for autoscroll on load
				window.history.pushState(null, null, '#' + img.id);
				this.Frm.EnableFields();
				img.parentNode.parentNode.style.opacity = 1;
				byId('SpeciesSexId').focus();
			}).catch(err => {
				alert(err);
			});
		},

		getImgIdXml: function(xml) {
			let imgXml = xml.getElementsByTagName('Image').item(0);

			return imgXml.getAttribute('Id');
		},

		insertImage: function(img) {
			let imgSrc, request;

			this.SetCurImgId(null);	// reset before inserting new -> make sure not an old id is used in form update/edit
			this.Frm.SetCurImgId(null);
			imgSrc = new URL(img.src);
			request = new Request(PHPDbFncUrl, {
				method: 'post',
				body: new URLSearchParams('Fnc=Insert&Img=' + imgSrc.pathname)
			});

			return fetch(request).then(response => {
				if (response.ok) {
					return this.parseXml(response);
				}
				else {
					throw new Error('insert failed: ' + response);
				}
			}).catch(console.log.bind(console));
		},

		editImage: function(img) {
			let request;

			request = new Request(PHPDbFncUrl, {
				method: 'post',
				body: new URLSearchParams('Fnc=Edit&ImgId=' + img.id) // init object not implemented yet in FF
			});

			return fetch(request).then(response => {
				if (response.ok) {
					return this.parseXml(response);
				}
				else {
					return new Error('edit failed' + response);
				}
			}).catch(console.log.bind(console));
		},

		/**
		 * Parses the response into Xml.
		 * @param {Response} response
		 * @returns {Promise}
		 */
		parseXml: function(response) {
			return response.text().then(text => {
				let doc, error, parser = new DOMParser();

				doc = parser.parseFromString(text, 'text/xml');
				error = doc.getElementsByTagName('parsererror');
				if (error.length > 0) {
					Promise.reject()
				}
				return doc;
			}).catch(console.log.bind(console));
		},

		/**
		 * Place image element on canvas.
		 * @param img
		 */
		placeImage: function(img) {
			var self = this,
				Canvas = byId('CanvasImgPreview'),
				ImgPrev = new Image();

			ImgPrev.src = img.src;
			while (Canvas.firstChild) {
				Canvas.removeChild(Canvas.firstChild);
			}
			ImgPrev.setAttribute('id', 'ImgPreview');
			ImgPrev.addEventListener('click', function() {
				self.ZoomImg(img, ImgPrev.width, ImgPrev.height);
			}, false);
			ImgPrev = Canvas.appendChild(ImgPrev);
		},

		/**
		 * Show image in full size centered on screen.
		 *
		 * @param {object} img HTMLImgElement
		 * @param {object} object with width (obj.W) and height (obj.H) attribute
		 */
		ZoomImg: function(ElImg, Dim) {
			var img = new Image();
			img.src = ElImg.src;
			if (Dim && typeof Dim.W != 'undefined') {
				img.style.width = Dim.W + 'px';
			}
			if (Dim && typeof Dim.H != 'undefined') {
				img.style.height = Dim.H + 'px';
			}
			var El = d.createElement('div');
			El.appendChild(img);
			var Div = d.createElement('div');
			Div.appendChild(El);
			El = d.createElement('div');
			El.appendChild(Div);
			El.setAttribute('class', 'ImgFullScreen');
			El.addEventListener('click', function() {
				this.parentNode.removeChild(this);
			}, false);
			d.body.appendChild(El);
		},

		/**
		 * Delete image data on keyup.
		 *
		 * @param object KeyboardEvent
		 */
		DelImage: function() {
			// TODO: this method should only be available to authenticated users (->PHP)
			var ImgId = this.GetCurImgId();
			if (!ImgId) {
				alert('select an image first.');
				return false;
			}
			if (confirm('delete image data?')) {
				var Ajax = new XmlHttp();
				Ajax.Method = 'POST';
				Ajax.Charset = 'UTF-8';
				Ajax.Query = 'Fnc=Del&ImgId=' + ImgId;
				Ajax.SetLoadingFnc(this, function() {
					this.Frm.DisableFields();
				});
				Ajax.SetDoneFnc(this, function() {
					byId(ImgId).parentNode.setAttribute('class', 'MarkOpen');
					byId(ImgId).removeAttribute('id');
					this.SetCurImgId(null);
					this.Frm.SetCurImgId(null);
					byId('ImgPreview').src = 'none';
					this.Frm.GetFormEl().reset();
					this.Frm.EnableFields();
					var arrTr = this.Explorer.ParEl.getElementsByTagName('tr');
					var i = arrTr.length - 1;
					for (; i > -1; i--) {
						arrTr[i].classList.remove('selected');
					}
				});
				Ajax.LoadData(PHPDbFncUrl);
			}
		},

		/**
		 * Advance to next image and select it.
		 */
		NextImage: function() {
			// current selected image (has an Id only after ajax request completed)
			let img, tr, el = byId(this.GetCurImgId());

			if (el) {
				tr = Tool.NextNode(el.parentNode.parentNode);	// img -> td -> tr
				if (!tr.classList.contains('dir')) {	// only process files, not folders
					img = tr.getElementsByTagName('img').item(0);
					if (img) {	// no next el if already last image in file list
						this.Explorer.HighlightRow(tr);
						// display exif data
						this.DisplExifData(img, byId('PaneLeftExifContent'));
						// update/edit/insert image
						this.SetImg(img);
					}
				}
				if (el) {
					el.scrollIntoView({behavior: 'smooth', block: 'start'});
				}
			}
			else {
				// find first tr and select it
				tr = document.querySelector('#ImgExplCont tr:not(.dir)');
				img = tr.getElementsByTagName('img').item(0);
				if (img) { // no next el if already last image in file list
					this.Explorer.HighlightRow(tr);
					// display exif data
					this.DisplExifData(img, byId('PaneLeftExifContent'));
					// update/edit/insert image
					this.SetImg(img);
				}
			}
		},

		/**
		 * Go to previous image in explorer and select it.
		 */
		PreviousImage: function() {
			let img, tr, el = byId(this.GetCurImgId());	// current selected image (has an Id only after ajax request completed)

			if (el) {
				tr = Tool.PreviousNode(el.parentNode.parentNode);	// img -> td -> tr
				if (!tr.classList.contains('dir')) {	// only process files, not folders
					img = tr.getElementsByTagName('img').item(0);
					if (img) {	// no next image if already first image in file list
						this.Explorer.HighlightRow(tr);
						// display exif data
						this.DisplExifData(img, byId('PaneLeftExifContent'));
						// update/edit/insert image
						this.SetImg(img);
					}
				}
				if (el) {
					el.scrollIntoView({behavior: 'smooth', block: 'end'});
				}
			}
			else {
				// find first tr and select it
				tr = document.querySelector('#ImgExplCont tr:not(.dir)');
				img = tr.getElementsByTagName('img').item(0);
				if (img) { // no next el if already last image in file list
					this.Explorer.HighlightRow(tr);
					// display exif data
					this.DisplExifData(img, byId('PaneLeftExifContent'));
					// update/edit/insert image
					this.SetImg(img);
				}
			}
		},

		/**
		 * Handles all main window keys.
		 * Note: Use ctrl instead of alt key, since there is less interference with existing keys such as arrow keys in select fields
		 */
		Keys: function(evt) {
			if (evt.ctrlKey) {
				switch(evt.key) {
					case 'ArrowRight':
						evt.stopPropagation();
						evt.preventDefault();
						this.NextImage();
						break;
					case 'ArrowDown':
						evt.stopPropagation();
						evt.preventDefault();
						this.NextImage();
						break;
					case 'ArrowLeft':
						evt.stopPropagation();
						evt.preventDefault();
						this.PreviousImage();
						break;
					case 'ArrowUp':
						evt.stopPropagation();
						evt.preventDefault();
						this.PreviousImage();
						break;
					case 'Delete':
						evt.stopPropagation();
						evt.preventDefault();
						this.DelImage();
						break;
					// ctrl + s save
					case 's':
						evt.stopPropagation();
						evt.preventDefault();
						this.Frm.SaveAll();
						break;
					// ctrl + c copy
					case 'c':
						if (this.isTextSelected(evt.target)) {
							break;  // allow copy when text is selected
						}
						evt.stopPropagation();
						evt.preventDefault();
						// if the field ImgDateOriginal is not empty we skip it to not overwrite date from exif
						let fld = byId('ImgDateOriginal');
						if (fld.value !== '') {
							fld.XmlInclude = false;
						}
						Tool.AddObj(this.Frm.SaveXml(), 'copy');
						fld.XmlInclude = true;
						break;
					// alt + v paste
					case 'v':
						if (this.isTextSelected(evt.target)) {
							break;  // allow copy when text is selected
						}
						evt.stopPropagation();
						evt.preventDefault();
						// if ImgLat and ImgLng are not empty (set by exif), we do not overwrite when pasting
						let lat = byId('ImgLat'), lng = byId('ImgLng');
						lat.overwrite = lat.value === '';
						lng.overwrite = lng.value === '';
						this.Frm.Fill(Tool.GetObjById('copy'));
						break;
				}
			}
		},

		isTextSelected: function(node) {
			// window.getSelection() only works on non text input nodes
			return window.getSelection().toString() !== '' || node.selectionStart !== undefined;
		},

		/**
		 * Fill in species information into form and set it as title as well as set the theme.
		 * @param evt
		 * @param select
		 */
		setSpeciesNames: function(evt, select) {
			if (evt.keyCode === keys.ENTER) {
				var store = select.store,
					id = select.get('value'),
					fld = byId('SpeciesSexId'),
					sexId = fld[fld.selectedIndex].value,
					sexText = fld[fld.selectedIndex].text,
					themeIds,
					// fetchItemByIdentity would send an additional request to get the other names
					nameDe = store._itemsByIdentity[id].NameDe,
					nameEn = store._itemsByIdentity[id].NameEn,
					nameLa = store._itemsByIdentity[id].NameLa;

				this.createSpeciesElements(id, nameDe, nameEn, nameLa, sexId, sexText);
				fld.focus();
				// set title
				fld = byId('ImgTitle');
				if (fld.value === '') {
					fld.value = nameDe;
				}
				// set theme
				// an image can have more than one theme
				fld = byId('Theme');
				if (fld.value === '') {
					// TODO: instead of not setting theme id when a theme is already set, we should just add it instead
					fld.value = store._itemsByIdentity[id].ThemeId;
				}
				// set image to public
				fld = byId('Public');
				fld.checked = true;
			}
		},

		/**
		 *  Application init function.
		 */
		init: function() {
			var self = this;
			// form setup
			// setup db functions
			var DataSrcKeywords = new JsonRest({
				target: PHPDbFncUrl
			});
			var Keywords = new ComboBox({
				store: DataSrcKeywords,
				searchAttr: 'Name',	// Name of property to use in json result returned from FotoDB::LoadData() e.g. { identifier: Id, items: [Id: 3, Name: 'Flug'] }
				searchDelay: 300,
				queryExpr: '${0}',
				query: {
					Fnc: 'FldLoadData',
					FldName: 'KeywordName'
				},
				autoComplete: false,
				pageSize: 20,
				onKeyUp: function(Evt) {
					var Id = (this.item && this.item.Id) || null;
					if (Evt.keyCode == keys.ENTER) {
						self.SetKeyword(Id, this.attr('value'));
						this.attr('value', '');
					}
				}
			}, 'KeywordName');

			// This array holds all form/element names, that have to be saved/read to/from database
			// and the name of the xml element it will be an attribute or child of.
			// Species module is set in SetScientificNames method.
			// location is set createLocationElements method
			var arrFld = [
				['ImgTitle', 'Image'],
				['ImgDesc', 'Image'],
				['Theme', 'Themes'],
				['Public', 'Image'],
				['FilmTypeId', 'Image'],
				['ImgDateManual', 'Image'],
				['RatingId', 'Image'],
				['ImgTechInfo', 'Image'],
				['ImgDateOriginal', 'Image'],
				['ImgLat', 'Image'],
				['ImgLng', 'Image'],
				['ShowLoc', 'Image'],
				['CountryId', 'Image']
			];
			this.mapTool = new GoogleMap();

			this.Frm = new DbForm(arrFld, this);
			this.Frm.init('FrmDbFoto');

			// File Explorer
			this.SetupExplorer();

			// events
			byId('FncSaveImg').addEventListener('click', function() {
				self.Frm.SaveAll();
			}, true);
			window.addEventListener('keydown', function(evt) { // use keydown instead of keypress because dijit traps events and stops bubbling
				self.Keys(evt);
			}, true);
			byId('LocationName').addEventListener('keyup', function(e) {
				if (e.keyCode == 13) {
					self.createLocationElements(null, this.value);
					this.value = "";
				}
			}, true);
			byId('Location').addEventListener('dblclick', function() {
				self.createLocationElements(this[this.selectedIndex].value, this[this.selectedIndex].text);
			}, true);
			byId('Location').addEventListener('keyup', function(e) {
				if (e.keyCode == 13) {
					self.createLocationElements(this[this.selectedIndex].value, this[this.selectedIndex].text);
				}
			}, true);
			byId('CountryId').addEventListener('change', function() {
				self.ReloadLocation();	// filter locations by country'
				self.removeLocationElements();		// clear list with locations when user changes country
				// Disabled below, because when reverse geocoding does not find country user looses current extent because jumping to country
				//map.findAddress(this[this.selectedIndex].text, 4);
			}, true);

			// Species module
			// We only want to use the same store for all three FilteringSelects ->
			// use query object to determine which value to set and which db column to query
			var DataSrcScientificNames = new QueryReadStore({
				url: PHPDbFncUrl,
				requestMethod: 'POST',
				fetch: function(Request) {
					var ColName, Val;
					if (lang.exists('query.NameDe', Request)) {
						Val = Request.query.NameDe;
						ColName = 'NameDe';
					}
					else if (lang.exists('query.NameEn', Request)) {
						Val = Request.query.NameEn;
						ColName = 'NameEn';
					}
					else if (lang.exists('query.NameLa', Request)) {
						Val = Request.query.NameLa;
						ColName = 'NameLa';
					}
					Request.serverQuery = {
						Fnc: 'FldLoadData',
						FldName: 'ScientificName',
						Val: Val,	// Has to be same as searchAttr property defined in FilteringSelect
						ColName: ColName
					};
					Request.queryOptions = {
						ignoreCase: false,
						cache: true
					};
					return this.inherited("fetch", arguments);
				}
			});
			var SpeciesFilterDe = new FilteringSelect({
				store: DataSrcScientificNames,
				searchAttr: 'NameDe',
				searchDelay: 300,
				queryExpr: '${0}',
				autoComplete: false,
				invalidMessage: 'No name found.',
				pageSize: 12,
				onKeyUp: function(evt) {
					self.setSpeciesNames(evt, this);
				}
			}, 'SpeciesNameDe');
			var SpeciesFilterEn = new FilteringSelect({
				store: DataSrcScientificNames,
				searchAttr: 'NameEn',
				searchDelay: 300,
				queryExpr: '${0}',
				autoComplete: false,
				invalidMessage: 'No name found.',
				pageSize: 12,
				onKeyUp: function(evt) {
					self.setSpeciesNames(evt, this);
				}
			}, 'SpeciesNameEn');
			var SpeciesFilterLa = new FilteringSelect({
				store: DataSrcScientificNames,
				searchAttr: 'NameLa',
				searchDelay: 300,
				queryExpr: '${0}',
				autoComplete: false,
				invalidMessage: 'No name found.',
				pageSize: 12,
				onKeyUp: function(evt) {
					self.setSpeciesNames(evt, this);
				}
			}, 'SpeciesNameLa');
			on(byId('SpeciesSexId'), 'keyup', function(e) {
				if (e.keyCode == 13) {
					SpeciesFilterDe.focus();
				}
			});
			on(byId('SpeciesSexId'), 'change', function(e) {
				SpeciesFilterDe.focus();
			});

			// create layout
			var El = new BorderContainer({style: 'width: 100%; height: 100%;'}, 'LayoutSplit1');
			new ContentPane({splitter: true, region: 'left', minSize: 200, style: 'width: 350px'}, 'Left1');
			new ContentPane({region: 'center'}, 'Right1');
			new BorderContainer({style: 'width: 100%; height: 100%;'}, 'LayoutSplit2');
			new ContentPane({region: 'center'}, 'Right2');
			var tc = new TabContainer({splitter: true, region: 'left', minSize: 300, style: 'width: 55%'}, 'TabCont');
			new ContentPane({title: 'image'}, 'Tab1');
			new ContentPane({title: 'location'}, 'Tab2');

			new BorderContainer({style: 'width: 100%; height: 100%;'}, 'LayoutSplit3');
			new ContentPane({splitter: true, region: 'top', style: 'height: 60%'}, 'ImgExplTop');
			new ContentPane({region: 'center'}, 'PaneLeftExifContent');
			El.startup();

			tc.watch("selectedChildWidget", function(name, oval, nval) {
				var point,
					lat = byId('ImgLat').value,
					lng = byId('ImgLng').value,
					mapTool = self.mapTool;

				if (!mapTool.map) {
					mapTool.initMap('map-container');
					self.initEvents(self.mapTool.map);
				}

				mapTool.clearMarkers();
				if (lat !== '' && lng !== '') {
					mapTool.addMarker({
						lat: lat,
						lng: lng,
						img: byId('ImgPreview').src
					}, 'image');

					point = mapTool.gmaps.LatLng(lat, lng);
					mapTool.map.setCenter(point);
					mapTool.map.setZoom(10);
				}
			});
		},

		/**
		 * Init events for the map tool.
		 * @param {google.maps} map
		 */
		initEvents: function(map) {
			var self = this;

			this.mapTool.gmaps.event.addDomListener(map, 'rightclick', function(evt) {
				var mapTool = self.mapTool;

				mapTool.clearMarkers();
				mapTool.setImgCoordinates(evt.latLng);
				mapTool.reverseGeocode(evt.latLng).then(lang.hitch(self.Frm, self.Frm.updateLocation));
			});

			on(byId('MapFindAddress'), 'click', function() {
				self.mapTool.findAddress(byId('MapAddress').value, 16);
			});
			on(byId('MapAddress'), 'keyup', function(evt) {
				if (evt.keyCode === 13) {
					self.mapTool.findAddress(this.value, 16);
				}
			});
		},

		/**
		 * Create HTMLDivElements containing Species names.
		 * @param {number} Id
		 * @param {string} NameDe
		 * @param {string} NameEn
		 * @param {string} NameLa
		 * @param SexId
		 * @param SexText
		 */
		createSpeciesElements: function(Id, NameDe, NameEn, NameLa, SexId, SexText) {
			var El = d.createElement('div');
			El.setAttribute('id', Id);
			El.XmlInclude = true;
			El.XmlElName = 'ScientificNames';
			var Child = El.appendChild(d.createElement('div'));	// Species name de
			Child.setAttribute('style', 'float: left; margin-right: 10px;');
			Child.appendChild(d.createTextNode(NameDe));
			Child = El.appendChild(d.createElement('div'));			// Species name en
			Child.setAttribute('style', 'float: left; margin-right: 10px;');
			Child.appendChild(d.createTextNode(NameEn));
			Child = El.appendChild(d.createElement('div'));			// Species name la
			Child.setAttribute('style', 'float: left; margin-right: 10px;');
			Child.appendChild(d.createTextNode(NameLa));
			Child = El.appendChild(d.createElement('div'));			// Species sex
			Child.setAttribute('id', SexId);
			Child.setAttribute('style', 'float: left; margin-right: 10px;');
			Child.appendChild(d.createTextNode(SexText));
			Child = El.appendChild(d.createElement('div'));			// del Species
			Child.addEventListener('click', function() {
				this.parentNode.parentNode.removeChild(this.parentNode);
			}, false);
			var img = d.createElement('img');
			img.setAttribute('src', '../dbprivate/layout/images/del_button.gif');
			Child.appendChild(img);
			byId('Species').appendChild(El);
		},

		/**
		 * Clear HTMLDivElement containing Species names
		 */
		removeSpeciesElements: function() {
			let el = byId('Species');

			while (el && el.firstChild) {
				el.removeChild(el.firstChild);
			}
		},

		/**
		 * Create HTMLDivElements containing location names.
		 *
		 * @param {number} Id image id
		 * @param {string} Name location name
		 */
		createLocationElements: function(Id, Name) {
			// check if name is not already in list before adding new
			var Nodes = byId('Locations').getElementsByTagName('div');
			var i = Nodes.length - 1;
			for (; i > -1; i--) {
				if (Nodes[i].firstChild && Nodes[i].firstChild.data == Name) {
					return;
				}
			}
			El = d.createElement('div');
			El.setAttribute('id', Id);
			El.XmlInclude = true;	// make available for Frm.SaveAll()
			El.XmlElName = 'Locations';	// name of parent Xml element
			var Child = El.appendChild(d.createElement('div'));	// location name de
			Child.setAttribute('style', 'float: left; margin-right: 10px;');
			Child.appendChild(d.createTextNode(Name));
			Child = El.appendChild(d.createElement('div'));			// del location button
			Child.addEventListener('click', function() {
				this.parentNode.parentNode.removeChild(this.parentNode);
			}, false);
			var img = d.createElement('img');
			img.setAttribute('src', '../dbprivate/layout/images/del_button.gif');
			Child.appendChild(img);
			byId('Locations').appendChild(El);
		},

		/**
		 * Remove all previously set location names.
		 */
		removeLocationElements: function() {
			var El = byId('Locations');
			while (El && El.firstChild) {
				El.removeChild(El.firstChild);
			}
		},

		/**
		 * Reload HTMLSelectElement Location and filter it by selected country. *
		 * If argument is given all locations are loaded if no country is selected. *
		 * @param {string} [argument] load all locations
		 */
		ReloadLocation: function() {
			var xhr = new XmlHttp();
			xhr.Method = 'POST';
			xhr.Charset = 'UTF-8';
			var El = byId('CountryId');
			var CountryId = '';
			if (El.selectedIndex > -1 && El[El.selectedIndex].value > -1) {
				CountryId = El[El.selectedIndex].value;
			}
			else if (!arguments[0]) {
				return false;
			}
			xhr.Query = 'Fnc=FldLoadData&FldName=Location&CountryId=' + CountryId;
			xhr.SetDoneFnc(xhr, function() {
				byId('Location').innerHTML = this.Result;
			});
			xhr.LoadData(PHPDbFncUrl);
		},

		/**
		 * Create HTMLDivElements containing keywords.
		 * @param {string} Text keyword
		 */
		SetKeyword: function(Id, Text) {
			// check if name is not already in list before adding new
			var Nodes = query('div', 'Keywords');
			array.forEach(Nodes, function(Item) {
				if (Item.childNodes[1].data == Text) {
					return false;
				}
			});
			var Node = document.createElement('div');
			Node.setAttribute('id', Id);
			Node.XmlInclude = true;
			Node.XmlElName = 'Keywords';
			var img = new Image();
			img.src = 'layout/images/del_button.gif';
			domStyle.set(img, {margin: '0px 5px 0px 0px', 'verticalAlign': 'bottom'});
			Node.addEventListener('click', function() {
				this.parentNode.removeChild(this);
			});
			Node.appendChild(img);
			Node.appendChild(document.createTextNode(Text));
			byId('Keywords').appendChild(Node);
		},

		ClearKeyword: function() {
			query('*', 'Keywords').orphan();
		},

		/**
		 * Method to load and display exif data.
		 * @param {object} img HTMLImageElement
		 * @param {object} elAppendTo HTMLDivElement to append data to
		 */
		DisplExifData: function(img, elAppendTo) {
			var xhr = new XmlHttp(),
				imgId = img.id,
				imgSrc = new URL(img.src);

			imgSrc = imgSrc.pathname.replace('/dbprivate/images', '');
			xhr.SetDoneFnc(this, function() {
				elAppendTo.innerHTML = xhr.Result;
				xhr = null;
			}, [elAppendTo, imgId]);
			xhr.LoadData(PHPExifUrl + '?img=' + imgSrc);
		},

		updateExif: function(img) {
			xhr.post(PHPDbFncUrl, {
				data: {
					Fnc: 'UpdateExif',
					ImgId: img.id
				}
			});
		}
	});
});