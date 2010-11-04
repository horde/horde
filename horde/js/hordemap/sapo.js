/**
 * SAPO specific version of HordeMap javascript.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @João Machado <joao.machado@co.sapo.pt>
*/

HordeMap.Map.SAPO = Class.create({
    initialize: function(opts) {},
    display: function(){
            var ifr = document.createElement('iframe');
            ifr.src = "/sapo/maps/sapoMapas.php";
            ifr.height = '100%';
            ifr.width = '100%';
            ifr.style.border = 'none';
            ifr.id='mapsIframe';
            ifr.frameBorder=0;
            ifr.scrolling='no';
            $('kronolithEventMap').appendChild(ifr);

            $('kronolithEventMap').style.width='620px';
            $('kronolithEventMap').style.height='257px';
    },
    destroy: function(){
        $('mapsIframe').contentWindow.map = null;
        $('mapsIframe').remove();
    },
    getZoom: function(){
        return $F('kronolithEventMapZoom');//stupid :D
    }
    ,//JP : This methods are ignored, mapsWidget will solve this :)
    setCenter: function(coords){},
    zoomToFit: function(zoom){},
    addMarker: function(mark){}
});

/**
 * SAPO geocoding service.
 */
HordeMap.Geocoder.SAPO = Class.create({

  _syndication: null,

  initialize: function(opts)
  {
        this.opts = opts || {};
  },

   geocode: function(address, callback, onErrorCallback)
   {
       queryToBeDone = address;//if maps iframe not ready, then iframe will use this text to search
       $('kronolithEventGeo_loading_img').style.display='none';

       KronolithCore.openTab($('kronolithEventLinkMap'));

       if(typeof($('mapsIframe').contentWindow.getResults) == 'function' && $('mapsIframe').contentWindow.navigation)
            $('mapsIframe').contentWindow.getResults(address); 
   },
   _onGeocodeComplete: function(r){ },
   reverseGeocode: function(lonlat, completeCallback, errorCallback) {},
   _onComplete: function(obj, args){},
   _onTimeout: function(){}
});


