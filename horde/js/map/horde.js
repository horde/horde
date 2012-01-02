/**
 * Horde mapping service.  This file provides a general API for interacting with
 * inline "slippy" maps. You must also include the file for the specific
 * provider support you want included.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
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
 * // opts.defaultBase      - Id of the baselayer to enable by default.
 * // opts.delayed          - Don't bind the map to the dom until display() is
 * //                         called.
 * // opts.elt              - DOM Node to place map in
 * // opts.onhover          - Event handler to run on feature hover (hightlight)
 * // opts.layers           - An Array of OpenLayers.Layer objects
 * // opts.mapClick         - Callback to handle a click on the map
 * // opts.markerBackground - Custom marker background image to use by default.
 * // opts.markerDragEnd    - Callback to handle when a marker is dragged.
 * // opts.markerImage      - Custom marker image to use by default.
 * // opts.onClick          - Callback for handling click events on features.
 * // opts.onHover          - Callback for handling hover events on features.
 * // opts.panzoom          - Use the larger PanZoomBar control. If false, will
 * //                         use the smaller ZoomPanel control.
 * //                       - Callback
 * // opts.useMarkerLayer   - Add a vector layer to be used to place markers.
 * // opts.hide             - Don't show markerlayer in LayerSwitcher
 * // opts.onBaseLayerChange - Callback fired when baselayer is changed.
 * // opts.zoomworldicon    - Show the worldzoomicon on the PanZoomBar control
 * //                         that resets/centers map.
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
        // @TODO: BC Break
        if (HordeMap.conf.markerImage) {
            opts.markerImage = HordeMap.conf.markerImage;
            opts.markerBackground = HordeMap.conf.markerBackground;
        }

        // defaults
        var o = {
            useMarkerLayer: false,
            draggableFeatures: true,
            showLayerSwitcher: true,
            markerLayerTitle: 'Markers',
            delayed: false,
            panzoom: true,
            zoomworldicon: false,
            layers: [],
            onHover: false,
            onClick: false,
            hide: true,
            onBaseLayerChange: false,
            defaultBaseLayer: false,
            // default stylemap
            styleMap: new OpenLayers.StyleMap({
                'default': {
                    externalGraphic: opts.markerImage,
                    backgroundGraphic: opts.markerBackground,
                    backgroundXOffset: 0,
                    backgroundYOffset: -7,
                    backgroundGraphicZIndex: 10,
                    pointRadius: (opts.pointRadius) ? opts.pointRadius : 10,
                }
            })
        };
        this.opts = Object.extend(o, opts || {});

        // Generate the base map object. Always use EPSG:4326 (WGS84) for display
        // and EPSG:900913 (spherical mercator) for projection for compatibility
        // with commercial mapping services such as Google, Yahoo etc...
        var options = {
            projection: new OpenLayers.Projection("EPSG:900913"),
            displayProjection: new OpenLayers.Projection("EPSG:4326"),
            units: "m",
            numZoomLevels: 18,
            maxResolution: 156543.0339,
            maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508.34),
            controls: [
                new OpenLayers.Control.Navigation(),
                new OpenLayers.Control.Attribution()
            ],
            styleMap: this.opts.styleMap
        };
        if (this.opts.panzoom) {
            options.controls.push(new OpenLayers.Control.PanZoomBar({ 'zoomWorldIcon': this.opts.zoomworldicon }));
        } else {
            options.controls.push(new OpenLayers.Control.ZoomPanel());
        }
        // Set the language
        OpenLayers.Lang.setCode(HordeMap.conf.language);
        this.map = new OpenLayers.Map((this.opts.delayed ? null : this.opts.elt), options);

        // Create the vector layer for markers if requested.
        // @TODO H5 BC break - useMarkerLayer should be permap, not per page
        if (this.opts.useMarkerLayer || HordeMap.conf.useMarkerLayer) {
            this.markerLayer = this.createVectorLayer(this.opts);
            this.opts.layers.push(this.markerLayer);
        }

        this.map.addLayers(this.opts.layers);
        if (this.opts.showLayerSwitcher) {
            this._layerSwitcher = new OpenLayers.Control.LayerSwitcher();
            this.map.addControl(this._layerSwitcher);
        }

        // Create a click control to handle click events on the map
        if (this.opts.mapClick) {
            var click = new OpenLayers.Control.Click({
                onClick: this._onMapClick.bind(this)
            });
            this.map.addControl(click);
            click.activate();
        }

        // Used for converting between internal and display projections.
        this._proj = new OpenLayers.Projection("EPSG:4326");
        if (this.opts.defaultBaseLayer) {
           this.map.setBaseLayer(this.map.getLayersByName(this.opts.defaultBaseLayer).pop());
        }
        this.map.zoomToMaxExtent();
        if (this.opts.onBaseLayerChange) {
            this.map.events.register('changebaselayer', null, this.opts.onBaseLayerChange);
        }
    },

    /**
     * Create a vector layer and attach to map. Can pass hover and click
     * handlers if this is the *only* layer to use them. Otherwise, use
     * addHighlightControl/addClickControl methods after all layers are
     * created.
     *
     * opts
     *   markerLayerTitle  - The title to show in the LayerSwitcher
     *   hide              - Do not show layer in LayerSwitcher
     *   onHover           - Hover handler
     *   onClick           - Click handler
     */
    createVectorLayer: function(opts)
    {
        var styleMap = opts.styleMap || this.styleMap;
        var layer = new OpenLayers.Layer.Vector(
            opts.markerLayerTitle,
            {
                'styleMap': styleMap,
                'rendererOptions': { zIndexing: true },
            }
        );
        if (opts.hide) {
            layer.displayInLayerSwitcher = false;
        }
        if (opts.draggableFeatures) {
            var dragControl = new OpenLayers.Control.DragFeature(
                layer,
                { onComplete: opts.markerDragEnd });

            this.map.addControl(dragControl);
            dragControl.activate();
        }

        if (opts.onHover) {
            this.addHighlightControl({
                'onHover': opts.onHover,
                'layers': layer
            });
        }

        if (opts.onClick) {
            this.addClickControl({
                'layers': layer,
                'onClick': opts.onClick
            });
        }

        return layer;
    },

    addHighlightControl: function(opts)
    {
        var selectControl = new OpenLayers.Control.SelectFeature(
            opts.layers, {
                hover: true,
                highlightOnly: true,
                renderIntent: 'temporary',
                eventListeners: {
                     beforefeaturehighlighted: opts.onHover,
                     featurehighlighted: opts.onHover,
                     featureunhighlighted: opts.onHover
                }
            }
        );
        this.map.addControl(selectControl);
        selectControl.activate();

        return selectControl;
    },

    /**
     * Add a click control to the map. HordeMap only supports one selectFeature
     * control for click handlers per map, though it may contain several layers.
     *
     * @param object opts
     *    'layers': [] All layers that should be included in the control layer.
     *              Note that any layers on top of layers that should handle
     *              clicks *must* be included in the array.
     *              This is an OL requirement.
     *    'active': [] Layers that should actually respond to the click request.
     */
    addClickControl: function(opts)
    {
        var clickControl = new OpenLayers.Control.SelectFeature(
            opts.layers, {
                'hover': false,
                'clickout': false,
                'toggle': true,
                'hover': false,
                'multiple': false,
                'renderIntent': 'temporary'
            }
        );
        opts.active.each(function(l) {
            l.events.on({
                'featureselected': opts.onClick
            });
       });
        this.map.addControl(clickControl);
        clickControl.activate();

        return clickControl;
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
     *
     * @param lonlat p    { 'lon': x, 'lat': y }
     * @para object opts  Options
     *    'styleCallback': callback to provide a custom styleobject for marker
     *    'layer': use this layer instead of this.markerLayer to place marker
     */
    addMarker: function(p, opts)
    {
        opts = Object.extend({ 'styleCallback': Prototype.K }, opts);
        var ll = new OpenLayers.Geometry.Point(p.lon, p.lat);
        ll.transform(this._proj, this.map.getProjectionObject());
        s = opts.styleCallback(this.markerLayer.style);
        var m = new OpenLayers.Feature.Vector(ll);
        if (opts.layer) {
            opts.layer.addFeatures([m]);
        } else {
            this.markerLayer.addFeatures([m]);
        }
        return m;
    },

    removeMarker: function(m, opts)
    {
        opts = opts || {};
        if (opts.layer) {
            opts.layer.destroyFeatures([m]);
        } else {
            this.markerLayer.destroyFeatures([m]);
        }
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
     */
    zoomToFit: function(layer)
    {
        if (!layer) {
            layer = this.markerLayer;
        }
        if (layer.getDataExtent()) {
            this.map.zoomToExtent(layer.getDataExtent());
        }
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
