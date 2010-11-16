/**
 * Default Yahoo Layers and geocoding services.
 * Need to load this after Yahoo's JS is loaded so the YAHOO_* constants are
 * defined.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 */
HordeMap.Yahoo = Class.create(
{
    getLayers: function(layer)
    {
        return {
        'streets': new OpenLayers.Layer.Yahoo("Yahoo Street", { 'sphericalMercator': true, 'wrapDateLine': true, 'minZoomLevel': 1 }),
        'sat': new OpenLayers.Layer.Yahoo("Yahoo Satellite", { 'type': YAHOO_MAP_SAT, 'sphericalMercator': true, 'wrapDateLine': true, 'minZoomLevel': 1 }),
        'hybrid': new OpenLayers.Layer.Yahoo("Yahoo Hybrid", { 'sphericalMercator': true, 'type': YAHOO_MAP_HYB, 'wrapDateLine': true, 'minZoomLevel': 1 })}
    }
});

/**
 * Yahoo geocoder:
 * http://developer.yahoo.com/maps/ajax/V3.8/index.html
 */
HordeMap.Geocoder.Yahoo = Class.create(
{
    _completeCallback: null,
    _errorCallback: null,

    initialize: function(map)
    {
        // Try to find an existing Yahoo layer and reuse it. Otherwise, we can
        // get away with just creating an unattached node.
        if (map) {
            var layers = map.layers;
            for (var i = 0; i < layers.length; i++) {
                if (layers[i].CLASS_NAME == 'OpenLayers.Layer.Yahoo') {
                    var mo = layers[i].mapObject;
                    this._map = mo;
                }
            }
        }
        if (!this._map) {
            this._map = new YMap(new Element('div'));
        }
    },

    /**
     * Perform a geocode action. Textual address -> latlng
     *
     */
    geocode: function(address, completeCallback, errorCallback)
    {
        this._completeCallback = completeCallback || function() {};
        this._errorCallback = errorCallback || function() {};
        YEvent.Capture(this._map, EventsList.onEndGeoCode, this._callback.bind(this));
        this._map.geoCodeAddress(address);
    },

    /**
     * Reverse geocode action
     *
     * No official reverse lookup via the ajax maps api
     */
    reverseGeocode: function(latlon, completeCallback, errorCallback)
    {
        this._completeCallback = completeCallback || function() {};
        this._errorCallback = errorCallback || function() {};
        this._reverseCallback([]);
        //YEvent.Capture(this._map, EventsList.onEndLocalSearch, this._reverseCallback.bind(this));
        //this._map.searchLocal(new YGeoPoint(latlon.lat, latlon.lon), 'address');
        //return [];
    },

    _callback: function(p)
    {
        if (p.success) {
            var results = [ { lon: p.GeoPoint.Lon, lat: p.GeoPoint.Lat, address: p.Address, precision: 1}];
        }

        this._completeCallback(results);
    },

    _reverseCallback: function(r)
    {
        this._completeCallback(r);
    }

});
/* Copyright (c) 2006-2008 MetaCarta, Inc., published under the Clear BSD
 * license.  See http://svn.openlayers.org/trunk/openlayers/license.txt for the
 * full text of the license. */


/**
 * @requires OpenLayers/Layer/SphericalMercator.js
 * @requires OpenLayers/Layer/EventPane.js
 * @requires OpenLayers/Layer/FixedZoomLevels.js
 */

/**
 * Class: OpenLayers.Layer.Yahoo
 *
 * Inherits from:
 *  - <OpenLayers.Layer.EventPane>
 *  - <OpenLayers.Layer.FixedZoomLevels>
 */
OpenLayers.Layer.Yahoo = OpenLayers.Class(
  OpenLayers.Layer.EventPane, OpenLayers.Layer.FixedZoomLevels, {

    /**
     * Constant: MIN_ZOOM_LEVEL
     * {Integer} 0
     */
    MIN_ZOOM_LEVEL: 0,

    /**
     * Constant: MAX_ZOOM_LEVEL
     * {Integer} 17
     */
    MAX_ZOOM_LEVEL: 17,

    /**
     * Constant: RESOLUTIONS
     * {Array(Float)} Hardcode these resolutions so that they are more closely
     *                tied with the standard wms projection
     */
    RESOLUTIONS: [
        1.40625,
        0.703125,
        0.3515625,
        0.17578125,
        0.087890625,
        0.0439453125,
        0.02197265625,
        0.010986328125,
        0.0054931640625,
        0.00274658203125,
        0.001373291015625,
        0.0006866455078125,
        0.00034332275390625,
        0.000171661376953125,
        0.0000858306884765625,
        0.00004291534423828125,
        0.00002145767211914062,
        0.00001072883605957031
    ],

    /**
     * APIProperty: type
     * {YahooMapType}
     */
    type: null,

    /**
     * APIProperty: sphericalMercator
     * {Boolean} Should the map act as a mercator-projected map? This will
     * cause all interactions with the map to be in the actual map projection,
     * which allows support for vector drawing, overlaying other maps, etc.
     */
    sphericalMercator: false,

    /**
     * Constructor: OpenLayers.Layer.Yahoo
     *
     * Parameters:
     * name - {String}
     * options - {Object}
     */
    initialize: function(name, options) {
        OpenLayers.Layer.EventPane.prototype.initialize.apply(this, arguments);
        OpenLayers.Layer.FixedZoomLevels.prototype.initialize.apply(this,
                                                                    arguments);
        if(this.sphericalMercator) {
            OpenLayers.Util.extend(this, OpenLayers.Layer.SphericalMercator);
            this.initMercatorParameters();
        }
    },

    /**
     * Method: loadMapObject
     */
    loadMapObject:function() {
        try { //do not crash!
            var size = this.getMapObjectSizeFromOLSize(this.map.getSize());
            this.mapObject = new YMap(this.div, this.type, size);
            this.mapObject.disableKeyControls();
            this.mapObject.disableDragMap();

            //can we do smooth panning? (moveByXY is not an API function)
            if ( !this.mapObject.moveByXY ||
                 (typeof this.mapObject.moveByXY != "function" ) ) {

                this.dragPanMapObject = null;
            }
        } catch(e) {}
    },

    /**
     * Method: onMapResize
     *
     */
    onMapResize: function() {
        try {
            var size = this.getMapObjectSizeFromOLSize(this.map.getSize());
            this.mapObject.resizeTo(size);
        } catch(e) {}
    },


    /**
     * APIMethod: setMap
     * Overridden from EventPane because we need to remove this yahoo event
     *     pane which prohibits our drag and drop, and we can only do this
     *     once the map has been loaded and centered.
     *
     * Parameters:
     * map - {<OpenLayers.Map>}
     */
    setMap: function(map) {
        OpenLayers.Layer.EventPane.prototype.setMap.apply(this, arguments);

        this.map.events.register("moveend", this, this.fixYahooEventPane);
    },

    /**
     * Method: fixYahooEventPane
     * The map has been centered, so the mysterious yahoo eventpane has been
     *     added. we remove it so that it doesnt mess with *our* event pane.
     */
    fixYahooEventPane: function() {
        var yahooEventPane = OpenLayers.Util.getElement("ygddfdiv");
        if (yahooEventPane != null) {
            if (yahooEventPane.parentNode != null) {
                yahooEventPane.parentNode.removeChild(yahooEventPane);
            }
            this.map.events.unregister("moveend", this,
                                       this.fixYahooEventPane);
        }
    },

    /**
     * APIMethod: getWarningHTML
     *
     * Returns:
     * {String} String with information on why layer is broken, how to get
     *          it working.
     */
    getWarningHTML:function() {
        return OpenLayers.i18n(
            "getLayerWarning", {'layerType':'Yahoo', 'layerLib':'Yahoo'}
        );
    },

  /********************************************************/
  /*                                                      */
  /*             Translation Functions                    */
  /*                                                      */
  /*    The following functions translate GMaps and OL    */
  /*     formats for Pixel, LonLat, Bounds, and Zoom      */
  /*                                                      */
  /********************************************************/


  //
  // TRANSLATION: MapObject Zoom <-> OpenLayers Zoom
  //

    /**
     * APIMethod: getOLZoomFromMapObjectZoom
     *
     * Parameters:
     * gZoom - {Integer}
     *
     * Returns:
     * {Integer} An OpenLayers Zoom level, translated from the passed in gZoom
     *           Returns null if null value is passed in.
     */
    getOLZoomFromMapObjectZoom: function(moZoom) {
        var zoom = null;
        if (moZoom != null) {
            zoom = OpenLayers.Layer.FixedZoomLevels.prototype.getOLZoomFromMapObjectZoom.apply(this, [moZoom]);
            zoom = 18 - zoom;
        }
        return zoom;
    },

    /**
     * APIMethod: getMapObjectZoomFromOLZoom
     *
     * Parameters:
     * olZoom - {Integer}
     *
     * Returns:
     * {Integer} A MapObject level, translated from the passed in olZoom
     *           Returns null if null value is passed in
     */
    getMapObjectZoomFromOLZoom: function(olZoom) {
        var zoom = null;
        if (olZoom != null) {
            zoom = OpenLayers.Layer.FixedZoomLevels.prototype.getMapObjectZoomFromOLZoom.apply(this, [olZoom]);
            zoom = 18 - zoom;
        }
        return zoom;
    },

    /************************************
     *                                  *
     *   MapObject Interface Controls   *
     *                                  *
     ************************************/


  // Get&Set Center, Zoom

    /**
     * APIMethod: setMapObjectCenter
     * Set the mapObject to the specified center and zoom
     *
     * Parameters:
     * center - {Object} MapObject LonLat format
     * zoom - {int} MapObject zoom format
     */
    setMapObjectCenter: function(center, zoom) {
        this.mapObject.drawZoomAndCenter(center, zoom);
    },

    /**
     * APIMethod: getMapObjectCenter
     *
     * Returns:
     * {Object} The mapObject's current center in Map Object format
     */
    getMapObjectCenter: function() {
        return this.mapObject.getCenterLatLon();
    },

    /**
     * APIMethod: dragPanMapObject
     *
     * Parameters:
     * dX - {Integer}
     * dY - {Integer}
     */
    dragPanMapObject: function(dX, dY) {
        this.mapObject.moveByXY({
            'x': -dX,
            'y': dY
        });
    },

    /**
     * APIMethod: getMapObjectZoom
     *
     * Returns:
     * {Integer} The mapObject's current zoom, in Map Object format
     */
    getMapObjectZoom: function() {
        return this.mapObject.getZoomLevel();
    },


  // LonLat - Pixel Translation

    /**
     * APIMethod: getMapObjectLonLatFromMapObjectPixel
     *
     * Parameters:
     * moPixel - {Object} MapObject Pixel format
     *
     * Returns:
     * {Object} MapObject LonLat translated from MapObject Pixel
     */
    getMapObjectLonLatFromMapObjectPixel: function(moPixel) {
        return this.mapObject.convertXYLatLon(moPixel);
    },

    /**
     * APIMethod: getMapObjectPixelFromMapObjectLonLat
     *
     * Parameters:
     * moLonLat - {Object} MapObject LonLat format
     *
     * Returns:
     * {Object} MapObject Pixel transtlated from MapObject LonLat
     */
    getMapObjectPixelFromMapObjectLonLat: function(moLonLat) {
        return this.mapObject.convertLatLonXY(moLonLat);
    },


    /************************************
     *                                  *
     *       MapObject Primitives       *
     *                                  *
     ************************************/


  // LonLat

    /**
     * APIMethod: getLongitudeFromMapObjectLonLat
     *
     * Parameters:
     * moLonLat - {Object} MapObject LonLat format
     *
     * Returns:
     * {Float} Longitude of the given MapObject LonLat
     */
    getLongitudeFromMapObjectLonLat: function(moLonLat) {
        return this.sphericalMercator ?
            this.forwardMercator(moLonLat.Lon, moLonLat.Lat).lon :
            moLonLat.Lon;
    },

    /**
     * APIMethod: getLatitudeFromMapObjectLonLat
     *
     * Parameters:
     * moLonLat - {Object} MapObject LonLat format
     *
     * Returns:
     * {Float} Latitude of the given MapObject LonLat
     */
    getLatitudeFromMapObjectLonLat: function(moLonLat) {
        return this.sphericalMercator ?
            this.forwardMercator(moLonLat.Lon, moLonLat.Lat).lat :
            moLonLat.Lat;
    },

    /**
     * APIMethod: getMapObjectLonLatFromLonLat
     *
     * Parameters:
     * lon - {Float}
     * lat - {Float}
     *
     * Returns:
     * {Object} MapObject LonLat built from lon and lat params
     */
    getMapObjectLonLatFromLonLat: function(lon, lat) {
        var yLatLong;
        if(this.sphericalMercator) {
            var lonlat = this.inverseMercator(lon, lat);
            yLatLong = new YGeoPoint(lonlat.lat, lonlat.lon);
        } else {
            yLatLong = new YGeoPoint(lat, lon);
        }
        return yLatLong;
    },

  // Pixel

    /**
     * APIMethod: getXFromMapObjectPixel
     *
     * Parameters:
     * moPixel - {Object} MapObject Pixel format
     *
     * Returns:
     * {Integer} X value of the MapObject Pixel
     */
    getXFromMapObjectPixel: function(moPixel) {
        return moPixel.x;
    },

    /**
     * APIMethod: getYFromMapObjectPixel
     *
     * Parameters:
     * moPixel - {Object} MapObject Pixel format
     *
     * Returns:
     * {Integer} Y value of the MapObject Pixel
     */
    getYFromMapObjectPixel: function(moPixel) {
        return moPixel.y;
    },

    /**
     * APIMethod: getMapObjectPixelFromXY
     *
     * Parameters:
     * x - {Integer}
     * y - {Integer}
     *
     * Returns:
     * {Object} MapObject Pixel from x and y parameters
     */
    getMapObjectPixelFromXY: function(x, y) {
        return new YCoordPoint(x, y);
    },

  // Size

    /**
     * APIMethod: getMapObjectSizeFromOLSize
     *
     * Parameters:
     * olSize - {<OpenLayers.Size>}
     *
     * Returns:
     * {Object} MapObject Size from olSize parameter
     */
    getMapObjectSizeFromOLSize: function(olSize) {
        return new YSize(olSize.w, olSize.h);
    },

    CLASS_NAME: "OpenLayers.Layer.Yahoo"
});
