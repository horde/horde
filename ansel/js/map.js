    /**
 * HordeMap support for Ansel
 *
 * Copyright 2009-2011 Horde LLC (http://www.horde.org/)
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

    /**
     * Builds the main map widget
     *
     * e - dom id
     * opts {
     *    'viewType': current view,
     *    'onHover':  callback for handling a feature's hover event or false
     *                if not used.
     * }
     */
    initMainMap: function(e, opts) {
        this.opts = opts;

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

        return this._initializeMap(e, {
            'styleMap': style,
            'onHover': opts.onHover,
            'layerSwitcher': true,
            'onClick': opts.onClick
        });
    },

    initMiniMap: function(e) {
        return this._initializeMap(e, {});
    },

    _initializeMap: function(e, opts)
    {
        if (this.mapInitialized[e]) {
            return this.maps[e];
        }
        var o = {
            'panzoom': false,
            'layerSwitcher': false,
            'onHover': false
        }
        this.opts = Object.extend(o, opts || {});
        var layers = [];
        if (Ansel.conf.maps.providers) {
            Ansel.conf.maps.providers.each(function(l) {
                var p = new HordeMap[l]();
                $H(p.getLayers()).values().each(function(e) { layers.push(e); });
            });
        }
        var mapOpts = {
            elt: e,
            //delayed: true,
            layers: layers,
            draggableFeatures: false,
            panzoom: this.opts.panzoom,
            showLayerSwitcher: this.opts.layerSwitcher,
            useMarkerLayer: true,
            markerImage: Ansel.conf.markeruri,
            markerBackground: Ansel.conf.shadowuri,
            onHover: this.opts.onHover,
            onClick: this.opts.onClick
            //markerDragEnd: this.onMarkerDragEnd.bind(this),
            //mapClick: this.afterClickMap.bind(this),
        }
        if (this.opts.styleMap) {
            mapOpts.styleMap = this.opts.styleMap;
        }
        this.maps[e] = new HordeMap.Map[Ansel.conf.maps.driver](mapOpts);
        this.mapInitialized[e] = true;
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
     */
    placeMapMarker: function(e, ll, opts)
    {
        var cb, marker;
        if (!opts) {
            opts = {};
        }
        if (opts.img) {
            marker = this.maps[e].addMarker(ll);
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
                this.maps[e].zoomToFit();
            }
        }

        return marker;
    },

    selectMarker: function(e, m)
    {
        this.maps[e].selectControl.highlight(m);
    },

    unselectMarker: function(e, m)
    {
        this.maps[e].selectControl.unhighlight(m);
    },

    onDomLoad: function()
    {
        this.maps.each(function(x) { x.display(); });
    }
}