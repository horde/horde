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

    initializeMap: function(e, opts)
    {
        if (this.mapInitialized[e]) {
            return;
        }
        var o = {
            'panzoom': false,
            'layerswitcher': false
        }
        this.opts = Object.extend(o, opts || {});
        var layers = [];
        if (Ansel.conf.maps.providers) {
            Ansel.conf.maps.providers.each(function(l) {
                var p = new HordeMap[l]();
                $H(p.getLayers()).values().each(function(e) {layers.push(e);});
            });
        }
        this.maps[e] = new HordeMap.Map[Ansel.conf.maps.driver]({
            elt: e,
            delayed: true,
            layers: layers,
            draggableFeatures: false,
            panzoom: this.opts.panzoom,
            showLayerSwitcher: this.opts.layerswitcher,
            useMarkerLayer: true,
            markerImage: Ansel.conf.markeruri,
            markerBackground: Ansel.conf.shadowuri
            //markerDragEnd: this.onMarkerDragEnd.bind(this),
            //mapClick: this.afterClickMap.bind(this)
        });

        this.maps[e].display();
        // Need to override this style here, since the OL CSS is loaded after
        // our main CSS, we can't override it in screen.css
        if (!this.opts.panzoom) {
            $(e).down('.olControlZoomPanel').setStyle({'top': '10px'});
        }
        this.mapInitialized[e] = true;
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
    placeMapMarker: function(e, ll, center, zoom, img)
    {

        var cb, marker;
        if (img) {
            cb = function(s) {
                return {
                    'externalGraphic': img,
                    'graphicWidth': 50,
                    'backgroundGraphic': Ansel.conf.pixeluri + '?c=222',
                    'backgroundHeight': 54,
                    'backgroundWidth': 54
                }
            };
            marker = this.maps[e].addMarker(ll, {'styleCallback': cb });
        } else {
            marker = this.maps[e].addMarker(ll);
        }
        if (center) {
            this.maps[e].setCenter(ll, zoom);
            if (!zoom) {
                this.maps[e].zoomToFit();
            }
        }

        return marker;
    },

    /**
     * Ensures the map tab is visible and sets UI elements accordingly.
     */
    ensureMap: function(e, opts)
    {
        if (!this.mapInitialized[e]) {
            this.initializeMap(e, opts);
        }
    },

    onDomLoad: function()
    {

    }
}