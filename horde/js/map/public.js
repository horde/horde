/**
 * Default layers from various public, open, APIs.  This file can also be
 * used as a template for creating layers and geocoding services from your
 * own hosted WMS or geocoding service.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 */
HordeMap.Public = Class.create(
{
    initialize: function(opts){},

    /**
     * Provide a very basic base set of layers from open sources.
     */
    getLayers: function(layers)
    {
        return {
            'streets': new OpenLayers.Layer.OSM('OpenStreetMap (Tiles@Home)', 'http://tah.openstreetmap.org/Tiles/tile/${z}/${x}/${y}.png', { 'minZoomLevel': 1, 'numZoomLevels': 17 }),
            'sat': new OpenLayers.Layer.WMS('Basic', 'http://labs.metacarta.com/wms/vmap0', { 'layers': 'basic', sphericalMercator:true })};
    }
});

/**
 * @TODO: Open geocoding service??
 */
HordeMap.Geocoder.Public = Class.create(
{
    intialize: function() {},
    geocode: function(address, completeCallback, errorCallback) {},
    reverseGeocode: function(latlon, completeCallback, errorCallback){}
})