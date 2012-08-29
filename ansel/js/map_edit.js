/**
 * Class for managing a HordeMap for manually geocoding images.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
AnselMapEdit = Class.create({
    _marker: null,
    _map: null,
    _geocoder: null,
    _opts: null,
    _img: null,

    initialize: function(img, opts)
    {
        this._img = img[0];
        this._opts = Object.extend(
            {
                'geocoder': 'Null'
            },
            opts
        );
        this._map = AnselMap.initEditMap('ansel_map', {
            'mapClick': this._mapClickHandler.bind(this),
            'markerDragEnd': this._markerMoveHandler.bind(this)
        });
        this._geocoder = new HordeMap.Geocoder[this._opts.geocoder](this._map, 'ansel_map');
        if (this._img.image_location) {
            this.setLocation(
                this._img.image_latitude,
                this._img.image_longitude,
                this._img.image_location);
        }
    },

    placeMapMarker: function(ll)
    {
        if (!this._marker) {
             this._marker = AnselMap.placeMapMarker(
                'ansel_map',
                ll,
                { 'center': true, 'zoom': 5 }
            );
        } else {
            AnselMap.moveMarker(
                'ansel_map',
                this._marker,
                ll,
                { 'center': true, 'zoom': 5 }
            );
        }
    },

    setLocation: function(lat, lon, loc)
    {
        this.placeMapMarker({ 'lat': lat, 'lon': lon });
        this._updateFields({ 'lat': lat, 'lon': lon }, loc);
    },

    geocode: function(loc)
    {
        this._geocoder.geocode(
            loc,
            this._geocodeCallback.curry(loc).bind(this),
            this._onError.bind(this));
    },

    save: function()
    {
        new Ajax.Request(this._opts.ajaxuri, {
            method: 'post',
            parameters: {
                action: 'geotag',
                img: this._opts.image_id,
                lat: this._marker.getLonLat().lat,
                lng: this._marker.getLonLat().lon
            },
            onComplete: function(transport) {
                if (transport.responseJSON.response > 0) {
                    window.opener.location.href = window.opener.location.href;
                    window.close();
                }
            }
        });
    },

    _geocodeCallback: function(loc, r)
    {
        this.setLocation(r[0].lat, r[0].lon, loc);
    },

    _mapClickHandler: function(p)
    {
        if (!this._marker) {
             this._marker = AnselMap.placeMapMarker('ansel_map', p.lonlat);
        } else {
            this._map.moveMarker(this._marker, p.lonlat);
        }
        this._geocoder.reverseGeocode(
            p.lonlat,
            this._getLocationCallback.bind(this),
            this._onError.bind(this));
    },

    _markerMoveHandler: function(r)
    {
        this._geocoder.reverseGeocode(
            r.getLonLat(),
            this._getLocationCallback.bind(this),
            this._onError.bind(this)
        );
    },

    _getLocationCallback: function(r)
    {
        // For privacy concerns, we don't return street address level data.
        if (r.length) {
            for (var i = 0; i < r.length; i++) {
                if (r[i].precision === 1) {
                    $('ansel_locationtext').update(r[i].address);
                    $('ansel_latlng').update(AnselMap.point2Deg(r[i]));
                    break;
                }
            }
        } else {
            $('ansel_locationtext').update('');
            $('ansel_latlng').update('');
        }
    },

    _updateFields: function(ll, loc)
    {
        $('ansel_locationtext').update(loc);
        $('ansel_latlng').update(AnselMap.point2Deg(ll));
    },

    _onError: function(r)
    {
        $('ansel_locationtext').update('');
    }

});
