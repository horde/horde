/**
 * OSM Tiles@Home layers.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
HordeMap.Osm = Class.create(
{
    initialize: function(opts){},

    /**
     * Provide a very basic base set of layers from open sources.
     */
    getLayers: function(layers)
    {
        return {
            'streets': new OpenLayers.Layer.OSM(
                'OpenStreetMap (Mapnik)',
                [
                    'http://a.tile.openstreetmap.org/${z}/${x}/${y}.png',
                    'http://b.tile.openstreetmap.org/${z}/${x}/${y}.png',
                    'http://c.tile.openstreetmap.org/${z}/${x}/${y}.png'
                ],
                { 'minZoomLevel': 1, 'numZoomLevels': 18 }
            )
        }
    }
});