define([
	'dojo/_base/declare',
	'//maps.googleapis.com/maps/api/js?key=' + dojoConfig.gmapsApiKey
], function(declare) {

	var
		/**
		 * Shortcut for document.getElementById
		 * @param {String} id
		 */
		byId = function(id) {
			return document.getElementById(id);
		};

	return declare(null, {

    gmaps: google.maps,
		map: null,
		mapLat: 45,	// initial map coordinates
		mapLng: 12,
		mapZoom: 5,	// initial map zoom
		markers: [],
		fotoDb: null,
		/** initial radius for reverse geocoding */
		geonamesRadius: 1,
		geonamesRadiusMax: 33,
		/** user name for geonames service */
		geonamesUser: 'speichnet',

		/**
		 *
		 * @param {String} mapId
		 */
		initMap: function(mapId) {
			var mapOptions = {
				center: new this.gmaps.LatLng(this.mapLat, this.mapLng),
				zoom: this.mapZoom,
				mapTypeId: this.gmaps.MapTypeId.HYBRID
			};

			this.map = new this.gmaps.Map(byId(mapId), mapOptions);
		},

		/**
		 * Use coordinates and radius to lookup geonames.
		 */
		reverseGeocode: function(latLng, radius = this.geonamesRadius) {
			let promise, marker,
				url = 'https://secure.geonames.org/findNearbyPlaceNameJSON',
				query = '?lat=' + latLng.lat() + '&lng=' + latLng.lng() + '&radius=' + radius + '&username=' + this.geonamesUser;

			marker = this.addMarker({position: latLng}, 'throbber');

			promise = fetch(url + query, {
				mode: 'cors',
				credentials: 'omit',
				method: 'GET'
			})
				.then(response => {
					if (response.ok) {
						return response.json();
					}
					else {
						throw new Error(response.status + ' ' + response.statusText);
					}
				})
				.then(json => {
					if (json.geonames.length === 0 && radius < this.geonamesRadiusMax) {
						radius *= 2;
						return this.reverseGeocode(latLng, radius);
					}
					else {

						return json.geonames;
					}
				})
				.catch(error => {
					alert('Reverse geocoding failed: ' + error);
				})
				.then(geonames => {
					marker.setMap(null);	// remove the loading icon
					return geonames;
				});

			return promise;
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
				geocoder = new this.gmaps.Geocoder();

			geocoder.geocode({ address: address}, function(results, status) {
				if (status === self.gmaps.GeocoderStatus.OK) {
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
				latLng = data.position || new this.gmaps.LatLng(data.lat, data.lng),
				image;

			switch(type) {
				case 'image':
					image = {
						anchor: new this.gmaps.Point(-1, 41),
						scaledSize: new this.gmaps.Size(60, 40),
						url: data.img
					};
					this.addMarker(data, 'crosshair');
					break;
				case 'throbber':
					image = {
						anchor: new this.gmaps.Point(8, 8),
						scaledSize: new this.gmaps.Size(16, 16),
						url: '../dbprivate/layout/images/ajax_loading.gif'
					};
					break;
				case 'crosshair':
					image = {
						anchor: new this.gmaps.Point(6, 6),
						scaledSize: new this.gmaps.Size(13, 13),
						url: '../dbprivate/layout/images/crosshair.gif'
					};
					break;
			}

			marker = new this.gmaps.Marker({
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