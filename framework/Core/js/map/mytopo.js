  /**
 * Mytopo layer. See http://www.mytopo.com/google/index.cfm for instructions
 * in obtaining an api key.
 *
 * Copyright 2009-2013 Horde LLC (http://www.horde.org/)
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
        this._id = HordeMap.conf.apikeys.mytopo.id;
        this._hash = HordeMap.conf.apikeys.mytopo.hash;
    },

    getLayers: function(layer)
    {
        return {'street': new OpenLayers.Layer.XYZ("MyTopo Topographic Map",
                    ['http://tileserver.mytopo.com/SecureTile/TileHandler.ashx?mapType=Topo&partnerID=' + this._id + '&hash=' + this._hash + '&x=${x}&y=${y}&z=${z}'],
                     {'sphericalMercator': true,
                      'minZoomLevel': 2,
                      'numZoomLevels': 17}) };
    }
});