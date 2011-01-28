/**
 * Horde mapping service.  This file provides a general API for interacting with
 * inline "slippy" maps. You must also include the file for the specific
 * provider support you want included.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

/**
 * Class for dealing with OpenLayers based inline mapping.
 *
 * Requires that the openlayer.js file has been included, as well as any js files
 * specific to any commercial mapping providers, such as google/yahoo etc...
 *
 * var options = {};
 * // opts.elt - dom node
 * // opts.layers - An Array of OpenLayers.Layer objects
 * // opts.delayed - don't bind the map to the dom until display() is called.
 * // opts.markerDragEnd - callback to handle when a marker is dragged.
 * // opts.mapClick - callback to handle a click on the map
 * var map = new HordeMap.OpenLayers(options);
 *
 */
HordeMap.Map.Horde = Class.create({

    map: null,
    markerLayer: null,
    _proj: null,

    initialize: function(opts)
    {
        // defaults
        var o = {
            showLayerSwitcher: true,
            delayed: false,
            layers: []
        };
        this.opts = Object.extend(o, opts || {});

        // Generate the base map object. Always use EPSG:4326 (WGS84) for display
        // and EPSG:900913 (spherical mercator) for projection for compatibility
        // with commercial mapping services such as Google, Yahoo etc...
        var options = {
            projection: new OpenLayers.Projection("EPSG:900913"),
            displayProjection: new OpenLayers.Projection("EPSG:4326"),
            units: "m",
            numZoomLevels:18,
            maxResolution: 156543.0339,
            maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508.34),

            // @TODO: configurable (allow smaller zoom control etc...
            // @TODO: custom LayerSwitcher control?
            controls: [new OpenLayers.Control.PanZoomBar({ 'zoomWorldIcon': true }),
                       new OpenLayers.Control.LayerSwitcher(),
                       new OpenLayers.Control.Navigation(),
                       new OpenLayers.Control.Attribution()],

           fallThrough: false
        };

        // Set the language to use
        OpenLayers.Lang.setCode(HordeMap.conf.language);
        this.map = new OpenLayers.Map((this.opts.delayed ? null : this.opts.elt), options);

        // Create the vector layer for markers if requested.
        if (HordeMap.conf.useMarkerLayer) {
            styleMap = new OpenLayers.StyleMap({
                externalGraphic: HordeMap.conf.markerImage,
                backgroundGraphic: HordeMap.conf.markerBackground,
                backgroundXOffset: 0,
                backgroundYOffset: -7,
                graphicZIndex: 11,
                backgroundGraphicZIndex: 10,
                pointRadius: 10
            });
            this.markerLayer = new OpenLayers.Layer.Vector("Markers",
                {
                    'styleMap': styleMap,
                    'rendererOptions': {yOrdering: true}
                });

            var dragControl = new OpenLayers.Control.DragFeature(this.markerLayer, { onComplete: this.opts.markerDragEnd });
            this.map.addControl(dragControl);
            dragControl.activate();
        }

        this.opts.layers.push(this.markerLayer);
        this.map.addLayers(this.opts.layers);

        if (this.opts.showLayerSwitcher) {
            this.map.addControl(new OpenLayers.Control.LayerSwitcher());
        }

        // Create a click control to handle click events on the map
        if (this.opts.mapClick) {
            var click = new OpenLayers.Control.Click({ onClick: this._onMapClick.bind(this) });
            this.map.addControl(click);
            click.activate();
        }

        // Used for converting between internal and display projections.
        this._proj = new OpenLayers.Projection("EPSG:4326");
        this.map.zoomToMaxExtent();
    },

    getZoom: function()
    {
        return this.map.getZoom();
    },

    display: function(n)
    {
        if (Object.isUndefined(this.map)) {
            return;
        }
        if (!n) {
            n = this.opts.elt;
        }
        this.map.render(n);
    },

    destroy: function()
    {
        this.map.destroy();
    },

    setCenter: function(p, z)
    {
        var ll = new OpenLayers.LonLat(p.lon, p.lat);
        ll.transform(this._proj, this.map.getProjectionObject());
        this.map.setCenter(ll, z);
    },

    /**
     */
    addMarker: function(p, opts)
    {
        var ll = new OpenLayers.Geometry.Point(p.lon, p.lat);
        ll.transform(this._proj, this.map.getProjectionObject());
        var m = new OpenLayers.Feature.Vector(ll);
        this.markerLayer.addFeatures([m]);

        return m;
    },

    removeMarker: function(m)
    {
        this.markerLayer.destroyFeatures([m]);
    },

    /**
     * Move a marker to new location.
     *
     * @param object m   An ol vector feature object representing the marker.
     * @param object ll  {lat: lon:}
     *
     * @return void
     */
    moveMarker: function(m, ll)
    {
        var point = new OpenLayers.LonLat(ll.lon, ll.lat);
        point.transform(this._proj, this.map.getProjectionObject());
        m.move(point);
    },

    /**
     * Zoom map to the best fit while containing all markers
     *
     * @param integer max  Highest zoom level (@TODO)
     */
    zoomToFit: function(max)
    {
        this.map.zoomToExtent(this.markerLayer.getDataExtent());
    },

    getMap: function()
    {
        return this.map;
    },

    getMapNodeId: function()
    {
        return this.opts.elt;
    },

    _onFeatureDragEnd: function(feature)
    {
        if (this.opts.markerDragEnd) {
            return this.opts.markerDragEnd(feature);
        }
    },

    _onMapClick: function(e)
    {
        // get*Px functions always return units in the layer's projection
        var lonlat = this.map.getLonLatFromViewPortPx(e.xy);
        lonlat.transform(this.map.getProjectionObject(), this._proj);
        if (this.opts.mapClick) { this.opts.mapClick({ lonlat: lonlat }); }
    }

});


    // Extension to OpenLayers to allow better abstraction:
    OpenLayers.Feature.Vector.prototype.getLonLat = function() {
        var ll = new OpenLayers.LonLat(this.geometry.x, this.geometry.y);
        ll.transform(new OpenLayers.Projection("EPSG:900913"), new OpenLayers.Projection("EPSG:4326"));
        return ll;
    };

    // Custom OL click handler - doesn't propagate a click event when performing
    // a double click
    OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
        defaultHandlerOptions: {
            'single': true,
            'double': false,
            'pixelTolerance': 0,
            'stopSingle': false,
            'stopDouble': false
        },

        initialize: function(options) {
            this.handlerOptions = OpenLayers.Util.extend({}, this.defaultHandlerOptions);
            OpenLayers.Control.prototype.initialize.apply(this, arguments);
            this.handler = new OpenLayers.Handler.Click(
                this, { 'click': options.onClick }, this.handlerOptions);
        }
    });


HordeMap.Geocoder.Horde = Class.create({});
