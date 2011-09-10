/**
 * Horde mapping service.  This file provides a general API for interacting with
 * inline "slippy" maps. You must also include the file for the specific
 * provider support you want included.
 *
 * Copyright 2009-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
 * // opts.markerImage
 * // opts.markerBackground
 * // opts.useMarkerLayer
 * var map = new HordeMap.OpenLayers(options);
 *
 */
HordeMap.Map.Horde = Class.create({

    map: null,
    markerLayer: null,
    _proj: null,
    _layerSwitcher: null,

    initialize: function(opts)
    {
        // defaults
        var o = {
            useMarkerLayer: false,
            draggableFeatures: true,
            showLayerSwitcher: true,
            markerLayerTitle: 'Markers',
            delayed: false,
            panzoom: true,
            zoomworldicon: true,
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

            // @TODO: custom LayerSwitcher control?
            controls: [
                new OpenLayers.Control.Navigation(),
                new OpenLayers.Control.Attribution()
            ],

           fallThrough: false
        };

        if (this.opts.panzoom) {
            options.controls.push(new OpenLayers.Control.PanZoomBar({ 'zoomWorldIcon': this.opts.zoomworldicon }));
        } else {
            options.controls.push(new OpenLayers.Control.ZoomPanel({ 'zoomWorldIcon': this.opts.zoomworldicon }));
        }
        // Set the language to use
        OpenLayers.Lang.setCode(HordeMap.conf.language);
        this.map = new OpenLayers.Map((this.opts.delayed ? null : this.opts.elt), options);

        // Create the vector layer for markers if requested.
        // @TODO H5 BC break - useMarkerLayer should be permap, not per page
        if (HordeMap.conf.markerImage) {
            this.opts.markerImage = HordeMap.conf.markerImage;
            this.opts.markerBackground = HordeMap.conf.markerBackground;
        }
        if (this.opts.useMarkerLayer || HordeMap.conf.useMarkerLayer) {
            var styleMap = new OpenLayers.StyleMap({
                externalGraphic: this.opts.markerImage,
                backgroundGraphic: this.opts.markerBackground,
                backgroundXOffset: 0,
                backgroundYOffset: -7,
                graphicZIndex: 11,
                backgroundGraphicZIndex: 10,
                pointRadius: 10
            });
            this.markerLayer = new OpenLayers.Layer.Vector(
                this.opts.markerLayerTitle,
                {
                    'styleMap': styleMap,
                    'rendererOptions': {yOrdering: true}
                });

            if (this.opts.draggableFeatures) {
                var dragControl = new OpenLayers.Control.DragFeature(
                    this.markerLayer,
                    { onComplete: this.opts.markerDragEnd });

                this.map.addControl(dragControl);
                dragControl.activate();
            }

            this.opts.layers.push(this.markerLayer);
        }
        this.map.addLayers(this.opts.layers);
        if (this.opts.showLayerSwitcher) {
            this._layerSwitcher = new OpenLayers.Control.LayerSwitcher();
            this.map.addControl(this._layerSwitcher);
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

    /**
     * @param string name  The feed display name.
     * @param string feed_url  The URL for the feed.
     * @param string proxy     A local proxy to get around same origin policy.
     *
     * @return OpenLayers.Layer  With narkers added to displayed map for each
     *                           georss entry.
     */
    addGeoRssLayer: function(name, feed_url, proxy)
    {
        var style = new OpenLayers.Style({ 'pointRadius': 20, 'externalGraphic': '${thumbnail}' });
        var layer = new OpenLayers.Layer.GML(name, feed_url, {
            projection: new OpenLayers.Projection("EPSG:4326"),
            format: OpenLayers.Format.GeoRSS,
            formatOptions: {
                createFeatureFromItem: function(item) {
                        var feature = OpenLayers.Format.GeoRSS.prototype
                                .createFeatureFromItem.apply(this, arguments);
                        feature.attributes.thumbnail =
                                this.getElementsByTagNameNS(
                                item, "*", "thumbnail")[0].getAttribute("url");
                        return feature;
                }
            },
            styleMap: new OpenLayers.StyleMap({
                    "default": style
            })
        });
        this.map.addLayer(layer);
        return layer;
    },

    removeGeoRssLayer: function(layer)
    {
        this.map.removeLayer(layer);
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

    zoomTo: function(z)
    {
        this.map.zoomTo(z);
    },

    /**
     * Adds a simple marker to the map. Will use the markerImage property
     * optionally passed into the map options. To add a feature with varying
     * markerImage, pass a stylecallback method that returns a suitable style
     * object.
     */
    addMarker: function(p, opts)
    {
        opts = Object.extend({ 'styleCallback': Prototype.K }, opts);
        var ll = new OpenLayers.Geometry.Point(p.lon, p.lat);
        ll.transform(this._proj, this.map.getProjectionObject());
        s = opts.styleCallback(this.markerLayer.style);
        var m = new OpenLayers.Feature.Vector(ll, null, s);
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
