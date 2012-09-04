/**
 * Open Mapquest layers
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
HordeMap.Mapquest = Class.create(
{
    initialize: function(opts){},

    /**
     * Provide a very basic base set of layers from open sources.
     */
    getLayers: function(layers)
    {
        return {
            'streets': new OpenLayers.Layer.OSM(
                'Open Mapquest',
                [
                    'http://otile1.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png',
                    'http://otile2.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png',
                    'http://otile3.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png',
                    'http://otile4.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png'
                ],
                { 'minZoomLevel': 1, 'numZoomLevels': 18 }
            ),
            'sat': new OpenLayers.Layer.OSM(
                'Open Mapquest Aerial',
                [
                    'http://oatile1.mqcdn.com/naip/${z}/${x}/${y}.png',
                    'http://oatile2.mqcdn.com/naip/${z}/${x}/${y}.png',
                    'http://oatile3.mqcdn.com/naip/${z}/${x}/${y}.png',
                    'http://oatile4.mqcdn.com/naip/${z}/${x}/${y}.png'
                ],
                { 'minZoomLevel': 1, 'numZoomLevels': 11 }
            )
        }
    }
});