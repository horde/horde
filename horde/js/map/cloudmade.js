/**
 * Default layers from various public, open, APIs.  This file can also be
 * used as a template for creating layers and geocoding services from your
 * own hosted WMS or geocoding service.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 */
HordeMap.Cloudmade = Class.create(
{
    initialize: function(opts)
    {
        this._key = HordeMap.conf.apikeys.cloudmade;
        this._style = 1;
    },

    getLayers: function(layer)
    {
        return {'street': new OpenLayers.Layer.XYZ("CloudMade Street",
                    ['http://a.tile.cloudmade.com/' + this._key + '/' + this._style + '/256/${z}/${x}/${y}.png',
                     'http://b.tile.cloudmade.com/' + this._key + '/' + this._style + '/256/${z}/${x}/${y}.png',
                     'http://c.tile.cloudmade.com/' + this._key + '/' + this._style + '/256/${z}/${x}/${y}.png'],
                     {'sphericalMercator': true,
                      'minZoomLevel': 2,
                      'numZoomLevels': 17}) };
    }
});