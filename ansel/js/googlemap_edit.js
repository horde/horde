/**
 * Javascript specific for the adding/moving a geotag via the map_edit.php page
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky
 * @package Ansel
 */

/**
 * Ansel_MapEdit class encapsulates various functions for searching/setting
 * locations.
 *
 * mapEdit = new Ansel_MapEdit(options)
 *
 * options is an object with the following required properties:
 *
 *    mainMap  - DOM id for the google map
 *    image_id - The image_id for the Ansel_Image we are tagging.
 *    gettext  - Various gettext strings (fetching, errortext)
 *    xurl     - The URL for imple.php
 *
 * and some optional properties:
 *
 *    statusId       - DOM id for a status message
 *    locationInput  - DOM id for the location search input field
 *    locationAction - DOM id for the Find button
 *    isNew          - Set to 1 if image is a newly geotagged image, 0 if we are
 *                     moving an existing geotag.
 *
 *
 */
Ansel_MapEdit = Class.create();
Ansel_MapEdit.prototype = {

    _options: null,
    _map: null,

    // {image_latitude: image_longitude:}
    ll: null,

    initialize: function(options)
    {
        this._options = Object.extend({
            statusId: 'status',
            locationInput: 'locationInput',
            locationAction: 'locationAction',
            isNew: '0'
            }, options);

        this._map = new Ansel_GMap({mainMap: this._options.mainMap,
                                    viewType: 'Edit',
                                    useManager: false,
                                    clickHandler: function(ol, ll, olll) {
                                        this._map.points[0].setLatLng(ll);
                                        this._map.points[0].image_data = {image_location: '',
                                                                          image_latitude: ll.lat(),
                                                                          image_longitude: ll.lng()};
                                        this._map.getLocations(this._map.points[0]);
                                    }.bind(this)});
        if (this._options.isNew) {
            this._map.maxZoom = 1;
        }

        this._map._getLocationCallback = this._map.getLocationCallback;
        this._map.getLocationCallback = function(points, marker) {
            this._map._getLocationCallback(points, marker);
        }.bind(this);

        this._map.addPoints(this._options.points);
        this._map.display();
        $(this._options.locationAction).observe('click', function(e) {this.getLocation();e.stop();}.bindAsEventListener(this));
        $(this._options.saveId).observe('click', function() {this.handleSave(this._options.image_id);}.bind(this));
    },

    handleSave: function(id)
    {
        var o = this._options;
        var params = { "values": "img=" + id + "/lat=" + this._map.points[0].getLatLng().lat() + "/lng=" + this._map.points[0].getLatLng().lng() };
        new Ajax.Request(o.xurl + "/action=geotag/post=values", {
            method: 'post',
            parameters: params,
            onComplete: function(transport) {
                if (transport.responseJSON.response > 0) {
                    window.opener.location.href = window.opener.location.href;
                    window.close();
                } // what to do if failure?
            }
        });
    },

    getLocation: function()
    {
        var o = this._options;

        $(this._options.statusId).update(this._options.gettext.fetching);
        if (this.ll) {
            //already have lat/lng from the autocompleter
            var gll = new GLatLng(this.ll.image_latitude, this.ll.image_longitude);
            this._map.points[0].setLatLng(gll);
            this._map.points[0].image_data = {image_location: $F(o.locationInput),
                                              image_latitude: gll.lat(),
                                              image_longitude: gll.lng()};

            this._map.getLocations(this._map.points[0]);
            this._map.mainMap.setCenter(gll, this._map.defaultZoom);
            $(o.statusId).update('');
            this.ll = null;
        } else {
            this._map.geocoder.getLocations($(o.locationInput).value, function(address) {
                if (address.Status.code == '200') {
                    // For now, just try the first returned spot - not sure how else to handle this
                    var lat = address.Placemark[0].Point.coordinates[1];
                    var lng = address.Placemark[0].Point.coordinates[0];
                    var gll = new GLatLng(lat, lng);
                    this._map.points[0].setLatLng(gll);
                    this._map.points[0].image_data = {image_location: '',
                                                      image_latitude: lat,
                                                      image_longitude: lng};

                    this._map.getLocations(this._map.points[0]);
                    this._map.mainMap.setCenter(gll, this._map.defaultZoom);
                    $(o.statusId).update('');
                } else {
                    $(o.statusId).update(o.gettext.errortext + address.Status.code);
                }
           }.bind(this));
       }
    },

    setLocation: function(lat, lng, loc)
    {
        var gll = new GLatLng(lat, lng);
        this._map.points[0].setLatLng(gll);
        this._map.points[0].image_data = {image_location: loc,
                                          image_latitude: lat,
                                          image_longitude: lng};

        this._map.getLocations(this._map.points[0]);
        this._map.mainMap.setCenter(gll, this._map.defaultZoom);
    }

}

/**
 * Override the Ajax.Autocompleter#updateChoices method so we can handle
 * receiving lat/lng points bound to the location.
 */
Ajax.Autocompleter.prototype.updateChoices = function(choices)
{
    var c = [];
    var hc = $H(choices);

    this.geocache = choices;

    if (!this.changed && this.hasFocus) {
        var re = new RegExp("(" + this.getToken() + ")", "i");
        var k = hc.keys();
        k.each(function(n) {
            c.push({
                l: n.escapeHTML().gsub(re, '<strong>#{1}</strong>'),
                v: n
            });
        });

        if (this.indicator) {
            this.indicator.hide();
        }

        if (k.size() == 1 && this.opts.autoSelect) {
            this.onSelect(k.first());
            if (this.knl) {
                this.knl.hide();
            }
        } else if (k.size()) {
            if (!this.knl) {
                this.knl = new KeyNavList(this.elt, { onChoose: this.onSelect.bind(this),
                                                        onShow: this.opts.onShow.bind(this),
                                                     domParent: this.opts.domParent });
            }
            this.knl.show(c);
        }
    }
}

/**
 * Override the Autocompler.Local#initialize method to take an Object instead
 * of an Array, and set the appropriate properties.
 */
Autocompleter.Local.prototype.initialize = function(element, obj, options) {
    this.baseInitialize(element, options);
    this.geocache = obj;
    this.opts.arr = $H(obj).keys();
}
