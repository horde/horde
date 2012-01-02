/**
 * HordeMap support for Ansel
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
 AnselMap = {

    mapInitialized: {},
    maps: {},
    opts: {},
    _iLayer: null,

    /**
     * Builds the main map widget
     *
     * e - dom id
     * opts {
     *    'onHover':    Callback for handling a feature's hover event or false
     *                  if not used.
     *    'onClick':    Callback for click event on the map (not a feature).
     *    'imageLayer': Separate layer for thumbnails? If this is set, a
     *                  second vector layer will be added and thumbnails will
     *                  be placed on this layer, separate from the 'markerOnly'
     *                  markers. Used from the Image View.
     * }
     */
    initMainMap: function(e, opts)
    {
        // Default OL StyleMap. We use a sybolizer since we have two different
        // types of features (thumbnail and marker) depending on the current view.
        var style = new OpenLayers.StyleMap({
                'default': {
                    'externalGraphic': '${thumbnail}',
                    'backgroundGraphic': '${background}'
                },
                'temporary': {}
        });

        // Symbolizer map for Default intent
        var markerStyleDefault = {
            'markerOnly': {
                'graphicHeight': 37,
                'graphicWidth': 32,
                'graphicYOffset': -37,
                'backgroundXOffset': 0,
                'backgroundYOffset': -42,
                'backgroundGraphicZIndex': 10,
                'graphicZIndex': 30
            },
            'noMarkerOnly': {
                'backgroundWidth': 54,
                'backgroundHeight': 54,
                'graphicWidth': 50,
                'graphicHeight': 50,
                'backgroundGraphicZIndex': 20,
                'graphicZIndex': 20
            }
        };

        // Symbolizer map for Temporary intent (hover)
        var markerStyleTemporary = {
            'markerOnly': {},
            'noMarkerOnly': {
                'backgroundGraphic': Ansel.conf.pixeluri + '?c=333333'
            }
        };
        style.addUniqueValueRules('default', 'markerOnly', markerStyleDefault);
        style.addUniqueValueRules('temporary', 'markerOnly', markerStyleTemporary);

        var map = this._initializeMap(e, {
            'styleMap': style,
            'layerSwitcher': true,
            'markerLayerTitle': opts.markerLayerText,
            'onBaseLayerChange': (opts.onBaseLayerChange) ? opts.onBaseLayerChange : false,
            'defaultBaseLayer': opts.defaultBaseLayer
        });

        if (!opts.imageLayer) {
            this.maps[e]._highlightControl = map.addHighlightControl({
                'onHover': opts.onHover,
                'layers': map.markerLayer
            });
            map.addClickControl({
                'onClick': opts.onClick,
                'layers': [map.markerLayer],
                'active': [map.markerLayer]
            });
        } else {
            this._iLayer = map.createVectorLayer({
                'markerLayerTitle': opts.imageLayerText,
                'styleMap': style,
            });
            map.map.addLayers([this._iLayer]);
            map.addHighlightControl({
                'layers': [this._iLayer, map.markerLayer],
                'onHover': opts.onHover
            });
            map.addClickControl({
                'layers': [this._iLayer, map.markerLayer],
                'active': [this._iLayer, map.markerLayer],
                'onClick': opts.onClick
            })
            map.map.raiseLayer(map.markerLayer, 1);
            map.map.raiseLayer(this._iLayer, -1);
            map.map.resetLayersZIndex();
        }

        return map;
    },

    /**
     * Inits the 'mini' map on the geotag widget
     *
     * @param string e  DOM id of the minimap
     */
    initMiniMap: function(e) {
        return this._initializeMap(e, {
            'styleMap': new OpenLayers.StyleMap(
                {
                    'externalGraphic': Ansel.conf.markeruri,
                    'pointRadius': 10
                }
            )
        });
    },

    /**
     * Inits the main map for the manual geotag view.
     *
     * @param string e    The DOM id of the editmap.
     * @param array opts  Options:
     *   mapClick       Handler for mapclick events
     *   markerDragEnd  Handler for feature drag events
     */
    initEditMap: function(e, opts) {
        return this._initializeMap(e, {
            'panzoom': true,
            'zoomworldicon': true,
            'layerSwitcher': true,
            'mapClick': opts.mapClick,
            'markerDragEnd': opts.markerDragEnd,
            'draggableFeatures': true,
            'styleMap': new OpenLayers.StyleMap(
                {
                    'externalGraphic': Ansel.conf.markeruri,
                    'backgroundGraphic': Ansel.conf.shadowuri,
                    'graphicHeight': 37,
                    'graphicWidth': 32,
                    'graphicYOffset': -37,
                    'backgroundXOffset': 0,
                    'backgroundYOffset': -42
                }
            )
        });
    },

    _initializeMap: function(e, op)
    {
        if (this.mapInitialized[e]) {
            return this.maps[e];
        }
        var o = {
            'panzoom': false,
            'layerSwitcher': false,
            'onHover': false,
            'markerDragEnd': Prototype.EmptyFunction,
            'markerLayerTitle': '',
            'onBaseLayerChange': false
        }
        var opts = Object.extend(o, op || {});
        var layers = [];
        if (Ansel.conf.maps.providers) {
            Ansel.conf.maps.providers.each(function(l) {
                var p = new HordeMap[l]();
                $H(p.getLayers()).values().each(function(e) { layers.push(e); });
            });
        }
        var mapOpts = {
            elt: e,
            layers: layers,
            draggableFeatures: (opts.draggableFeatures) ? true : false,
            panzoom: opts.panzoom,
            zoomworldicon: (opts.zoomworldicon) ? opts.zoomworldicon : false,
            showLayerSwitcher: opts.layerSwitcher,
            useMarkerLayer: true,
            markerImage: Ansel.conf.markeruri,
            markerBackground: Ansel.conf.shadowuri,
            pointRadius: 20,
            onHover: opts.onHover,
            onClick: opts.onClick,
            markerDragEnd: opts.markerDragEnd,
            mapClick: (opts.mapClick) ? opts.mapClick.bind(this) : Prototype.EmptyFunction,
            delayed: (opts.delayed) ? true : false,
            markerLayerTitle: opts.markerLayerTitle
        }
        if (opts.styleMap) {
            mapOpts.styleMap = opts.styleMap;
        }
        mapOpts.onBaseLayerChange = opts.onBaseLayerChange;

        this.maps[e] = new HordeMap.Map[Ansel.conf.maps.driver](mapOpts);
        this.mapInitialized[e] = true;
        if (opts.defaultBaseLayer) {
            this.maps[e].map.setBaseLayer(this.maps[e].map.getLayersByName(opts.defaultBaseLayer).pop());
        }

        return this.maps[e];
    },

    resetMap: function(e)
    {
        this.mapInitialized[e] = false;
        if (this.map[e]) {
            this.map[e].destroy();
            this.map[e] = null;
        }
    },

    /**
     * Place the event marker on the map, at point ll, ensuring it exists.
     * Optionally center the map on the marker and zoom. Zoom only honored if
     * center is set, and if center is set, but zoom is null, we zoomToFit().
     *
     * @param string e    The DOM id of the map we are placing the marker on.
     * @param latlon ll   { 'lat': x, 'lon': y }
     * @param object opts Options
     *   img         URI for image thumbnail to use for this marker.
     *   image_id    The image_id this marker represents
     *   markerOnly  We should place a traditional marker, not a thumbnail.
     *   background  The marker background URI
     *   image_link  The URL to the image view for this marker.
     *   zoom        We should auto zoom to this zoom level after placing.
     *   center      Auto center the map after placing the marker.
     */
    placeMapMarker: function(e, ll, opts)
    {
        var marker;
        if (!opts) {
            opts = {};
        }
        if (opts.img) {
            if (this._iLayer && opts.markerOnly == 'noMarkerOnly') {
                marker = this.maps[e].addMarker(ll, { 'layer': this._iLayer });
            } else {
                marker = this.maps[e].addMarker(ll);
            }
            marker.attributes['thumbnail'] = opts.img;
            marker.attributes['image_id'] = opts.image_id;
            marker.attributes['markerOnly'] = opts.markerOnly;
            marker.attributes['background'] = opts.background;
            marker.attributes['image_link'] = opts.image_link;
        } else {
            marker = this.maps[e].addMarker(ll);
        }
        if (opts.center) {
            this.maps[e].setCenter(ll, opts.zoom);
            if (!opts.zoom) {
                this.maps[e].zoomToFit((this._iLayer) ? this._iLayer : false);
            }
        }

        return marker;
    },

    /**
     * Move an existing marker.
     *
     * @param string e     The DOM id for the map the marker exists on.
     * @param object m     A marker object representing the marker
     * @param latlon ll    { 'lat': x, 'lon': x },
     * @param object opts  Options hash
     *    center  Center map after moving?
     *    zoom    Zoom level to set map to after moving.
     */
    moveMarker: function(e, m, ll, opts)
    {
        this.maps[e].moveMarker(m, ll);
        if (opts.center) {
            this.maps[e].setCenter(ll, opts.zoom);
            if (!opts.zoom) {
                this.maps[e].zoomToFit();
            }
        }
    },

    /**
     * Manually mark a marker as 'selected'. This has the effect of changing the
     * marker's render intent to indicate it is highlighted. Has the same effect
     * as hovering over the marker.
     */
    selectMarker: function(e, m)
    {
        this.maps[e]._highlightControl.highlight(m);
    },

    /**
     * Unselect a marker. Changes the markers render intent back to default.
     * Same effect as mouseout.
     */
    unselectMarker: function(e, m)
    {
        this.maps[e]._highlightControl.unhighlight(m);
    },

    /**
     * Utility method for rendering a lat/lon pair.
     */
    point2Deg: function(ll) {
             function dec2deg(dec, lat)
             {
                 var letter = lat ? (dec > 0 ? "N" : "S") : (dec > 0 ? "E" : "W");
                 dec = Math.abs(dec);
                 var deg = Math.floor(dec);
                 var min = Math.floor((dec - deg) * 60);
                 var sec = (dec - deg - min / 60) * 3600;
                 return deg + "&deg; " + min + "' " + sec.toFixed(2) + "\" " + letter;
             }

             return dec2deg(ll.lat, true) + " " + dec2deg(ll.lon);
    },

    onDomLoad: function()
    {
        this.maps.each(function(x) { x.display(); });
    }

}