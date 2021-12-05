define(['dojo/_base/declare'], function(declare) {

  return declare(null, {

    /** initial radius for reverse geocoding */
    radius: 1,

    radiusMax: 33,

    /** user name for geonames service */
    user: 'speichnet',

    url: 'https://secure.geonames.org/findNearbyPlaceNameJSON',

    /**
     *
     * @param latLng
     * @param [radius]
     * @return {Promise<Response>}
     */
    reverseGeocode(latLng, radius = this.radius) {
      let promise, query;

      query = '?lat=' + latLng.lat() + '&lng=' + latLng.lng() + '&radius=' + radius + '&username=' + this.user;

      promise = fetch(this.url + query, {
        mode: 'cors',
        credentials: 'omit',
        method: 'GET'
      })
        .then(response => {
          if (response.ok) {
            return response.json();
          } else {
            throw new Error(response.status + ' ' + response.statusText);
          }
        })
        .then(json => {
          if (json.geonames.length === 0 && radius < this.radiusMax) {
            radius *= 2;
            return this.reverseGeocode(latLng, radius);
          } else {

            return json.geonames;
          }
        })
        .catch(error => {
          alert('Reverse geocoding failed: ' + error);
        });

      return promise;
    },

    /**
     *
     * @param {Array<Object>} geonames
     * @param [idx]
     * @return {*}
     */
    getCountry(geonames, idx = 0) {
      return geonames[idx].countryName;
    },

    getName(geonames, idx = 0){
      return geonames[idx].name;
    },

    getAdminName1(geonames, idx = 0) {
      return geonames[idx].adminName1 || geonames[idx].adminName;
    }
  });
});