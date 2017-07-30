define([
	'dojo/_base/declare',
	'dojo/Deferred',
	'dojo/request/xhr',
	'dojo/dom-geometry',
	'dojo/dom-style',
	'fotodb/gmapLoader!http://maps.google.com/maps/api/js?key=AIzaSyAW3fEVRPQKGe4jxsp4T0pPFVQ9eJ-o86g&v=3&language=' + dojoConfig.locale
], function(declare,	Deferred, xhr, domGeometry, domStyle) {

	var gmaps = google.maps,
		/**
		 * Shortcut for document.getElementById
		 * @param {String} id
		 */
		byId = function(id) {
			return document.getElementById(id);
		};

	return declare(null, {

		map: null,
		mapLat: 45,	// initial map coordinates
		mapLng: 12,
		mapZoom: 5,	// initial map zoom
		markers: [],
		fotoDb: null,

		/**
		 *
		 * @param {String} mapId
		 */
		initMap: function(mapId) {
			var mapOptions = {
				center: new gmaps.LatLng(this.mapLat, this.mapLng),
				zoom: this.mapZoom,
				mapTypeId: gmaps.MapTypeId.HYBRID
			};

			this.map = new gmaps.Map(byId(mapId), mapOptions);
		},

		/**
		 * Convert point coordinates into location names and fill form.
		 */
		reverseGeocode: function(latLng) {
			var dfd = new Deferred();

			if (!latLng) {
				// user only clicked (closed) info marker window
				dfd.cancel();
				return dfd;
			}

			var target = '../dbprivate/library/reversegeocode.php?Lat=' + latLng.lat() + '&Lng=' + latLng.lng(),
				marker = this.addMarker({ position: latLng }, 'throbber');

			return xhr.get(target, {
				handleAs: 'xml'

			}).then(function(result) {

				marker.setMap(null);	// remove the loading icon
				return result;
			});
		},

		/**
		 * Display image on map and set its coordinates in the form.
		 * @param {google.maps.Marker} latLng
		 */
		setImgCoordinates: function(latLng) {
			byId('ImgLat').value = latLng.lat();
			byId('ImgLng').value = latLng.lng();

			// image marker
			if (byId('ImgPreview').src !== '') {
				this.addMarker({
					position: latLng,
					img: byId('ImgPreview').src
				}, 'image');
			}
		},

		clearMarkers: function() {
			for (var i = 0, len = this.markers.length; i < len; i++) {
				this.markers[i].setMap(null);
			}
		},

		/**
		 * Jump directly to an address on the map.
		 * @param {String} address
		 * @param {Number} zoomLevel
		 */
		findAddress: function(address, zoomLevel) {
			var self = this,
				geocoder = new gmaps.Geocoder();

			geocoder.geocode({ address: address}, function(results, status) {
				if (status === gmaps.GeocoderStatus.OK) {
					self.map.setCenter(results[0].geometry.location);
					self.addMarker({
						position: results[0].geometry.location
					}, 'crosshair');
					if (zoomLevel) {
						self.map.setZoom(zoomLevel);
					}
					geocoder = null;
				}
				else {
					console.log('Geocode was not successful for the following reason: ' + status);
					geocoder = null;
				}
			});
		},

		/**
		 * Creates and returns a marker of given type.
		 * Marker types are 'image', 'throbber', and 'crosshair'
		 * @param {Object} data json
		 * @param {String} type type of marker to create
		 * @return {google.maps.Marker}
		 */
		createMarker: function(data, type) {
			var marker,
				latLng = data.position || new gmaps.LatLng(data.lat, data.lng),
				image;

			switch(type) {
				case 'image':
					image = {
						anchor: new gmaps.Point(-1, 41),
						scaledSize: new gmaps.Size(60, 40),
						url: data.img
					};
					this.addMarker(data, 'crosshair');
					break;
				case 'throbber':
					image = {
						anchor: new gmaps.Point(8, 8),
						scaledSize: new gmaps.Size(16, 16),
						url: '../dbprivate/layout/images/ajax_loading.gif'
					};
					break;
				case 'crosshair':
					image = {
						anchor: new gmaps.Point(6, 6),
						scaledSize: new gmaps.Size(13, 13),
						url: '../dbprivate/layout/images/crosshair.gif'
					};
					break;
			}

			marker = new gmaps.Marker({
				icon: image || null,
				position: latLng,
				map: data.map || this.map
			});

			return marker;
		},

		/**
		 * Adds an image marker to the map.
		 * Marker types are 'image', 'throbber', and 'crosshair'.
		 * @param {Object} data json
		 * @param {String} type type of marker to create
		 * @return {google.maps.Marker}
		 */
		addMarker: function(data, type) {
			var marker = this.createMarker(data, type);

			this.markers.push(marker);
			return marker;
		}

	});
});