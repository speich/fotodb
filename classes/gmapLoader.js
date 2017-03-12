/**
 * Dojo AMD Google Maps Loader Plugin
 *	https://gist.github.com/ca0v/7450696
 * example: require(["plugins/async!http://maps.google.com/maps/api/js?v=3&sensor=false"], function () {// can use google.maps now});
 */
define([ 'dojo/_base/kernel'], function(kernel) {
	'use strict';

	var w = kernel.global,
		cb = '_googleApiLoadCallback';

	return {
		load: function(param, req, loadCallback) {
			if (!cb) {
				return;
			}
			w[cb] = function() {
				delete w[cb];
				cb = null;
				loadCallback();
			};
			require([param + "&callback=" + cb]);
		}
	};
});