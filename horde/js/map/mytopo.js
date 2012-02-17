/**
 * Mytopo layer. See http://www.mytopo.com/google/index.cfm for instructions
 * in obtaining an api key, though it seems that the public key they use on
 * their browse page works. (mytopoz63g9R)
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 */
HordeMap.Mytopo = Class.create(
{
    initialize: function(opts)
    {
        this._key = HordeMap.conf.apikeys.mytopo;
    },

    getLayers: function(layer)
    {
        return {'street': new OpenLayers.Layer.XYZ("MyTopo Topographic Map",
                    ['http://maps.mytopo.com/' + this._key + '/tilecache.py/1.0.0/topoG/${z}/${x}/${y}.png'],
                     {'sphericalMercator': true,
                      'minZoomLevel': 2,
                      'numZoomLevels': 17}) };
    }
});