/**
 * SAPO specific version of HordeMap javascript.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
HordeMap.Map.SAPO = Class.create({
    map: null,
    markerLayer: null,

    initialize: function(opts)
    {
        // defaults
        var o = {
            showLayerSwitcher: true,
            delayed: false,
            layers: [],
            fallThrough: false
        };
        this.opts = Object.extend(o, opts || {});
        this.map = new SAPO.Maps.Map(this.opts.elt);
        this.map.addControl(new SAPO.Maps.Control.MapType());
        this.map.addControl(new SAPO.Maps.Control.Navigation());
        this.markerLayer = new SAPO.Maps.Markers('Markers');
        this.map.addMarkers(this.markerLayer);
        this.map.events.register('click', this, this._onMapClick.bind(this));
    },


    /**
     * Set the map center and zoom level.
     *
     * @param object p   {lon:, lat:}
     * @param integer z  Zoom level
     *
     * @return void
     */
   setCenter: function(p, z)
   {
        this.map.setMapCenter(new OpenLayers.LonLat(p.lon, p.lat), z);
   },

    /**
     * Display the map if it is not visible, or move to a new container.
     * (Not implemented in SAPO driver).
     *
     * @param n
     * @return
     */
    display: function(n)
    {
        return;
    },

    destroy: function()
    {
        this.map.destroy();
    },

    /**
     * Add a marker to the map.
     *
     * @param object p {lat:, lon: }
     * @param object o Options object
     * @param object e  Event handlers
     *                  {context: <context to bind to> , click: , mouseover: <etc...>}
     */
    addMarker: function(p, o, e)
    {
        var marker = new SAPO.Maps.Marker(new OpenLayers.LonLat(p.lon, p.lat), o || {});
        this.markerLayer.addMarker(marker);

        e = e || {};
        e.context = e.context || marker;

        if (e.click) { marker.registerEvent('click', e.context, e.click) };
        if (e.mouseover) { marker.registerEvent('mouseover', e.context, e.mouseover) };
        if (e.mouseout) { marker.registerEvent('mouseout', e.context, e.mouseout) };
        if (e.drag) { marker.registerEvent('drag', e.context, e.drag) };
        if (e.dragend) { marker.registerEvent('dragend', e.context, e.dragend) };
        if (e.dragstart) { marker.registerEvent('dragstart', e.context, e.dragstart) };

        return marker;
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
        m.setLonLat(point);
    },

    /**
     * Zoom map to the best fit while containing all markers
     *
     * @param integer max  Highest zoom level (@TODO)
     */
    zoomToFit: function(max)
    {
        this.map.zoomToExtent(this.map.vectorLayer.getDataExtent());
    },

    removeMarker: function(m)
    {
        this.markerLayer.removeMarker(m);
    },

    _onMapClick: function(e)
    {
        var lonlat = this.map.getLonLatFromContainerPixel(e.xy);
        if (this.opts.mapClick) { this.opts.mapClick({ lonlat: lonlat }); }
    },

    getMap: function()
    {
        return this.map;
    },

    getMapNodeId: function()
    {
        return this.map.div;
    }

});

/**
 * SAPO geocoding service.
 */
HordeMap.Geocoder.SAPO = Class.create({

   _syndication: null,

   initialize: function(opts)
   {
        this.opts = opts || {};
        this.gc = new SAPO.Maps.Geocoder();
        this.syndication = new SAPO.Communication.Syndication();
   },

   /**
    * Perform a geocode operation. Textual address => latlng.
    *
    * @param string address            The textual address to geocode.
    * @param function callback         The callback function.
    * @param function onErrorCallback  The error callback.
    *
    *
    */
   geocode: function(address, callback, onErrorCallback)
   {
       this._userGeocodeCallback = callback;
       this._errorCallback = onErrorCallback || function() {};
       this.gc.getLocations(address, this._onGeocodeComplete.bind(this), callback, this.opts);
   },

   _onGeocodeComplete: function(r)
   {
       var ll = [];
       r.each(function(i) {
          var p =  { lat: i.Latitude, lon: i.Longitude };
          ll.push(p);
       });
       if (ll.length) {
           this._userGeocodeCallback(ll);
       } else {
           this._errorCallback(r);
       }
   },

   /**
    * Perform a reverse geocode operation. latlng -> textual address.
    *
    * Hack to support reverse geocoding by using the GetRoute API. Will
    * refactor this once an actual reverse geocoder is available via SAPO.
    */
   reverseGeocode: function(lonlat, completeCallback, errorCallback)
   {
       if (!lonlat || !completeCallback) {
           return;
       }

       // Save for later...yes, a hack.
       this._ll = lonlat;

       if (!this._syndication) {
           this._syndication = new SAPO.Communication.Syndication();
       }

       var url = "http://services.sapo.pt/Maps/GetRoute/JSON?mode=rapido&pts=" + lonlat.lon + "," + lonlat.lat + "," + lonlat.lon + "," + lonlat.lat;

       var reqID = this.syndication.push(url, {
           timeout: 4,
           onComplete: this._onComplete.bind(this),
           onTimeout: this._onTimeout.bind(this),
           optOnComplete: {
               onComplete: completeCallback,
               onError: errorCallback
           }
       });

       this.syndication.run(reqID);
   },

   /**
    * onComplete
    */
   _onComplete: function(obj, args)
   {
       var place = '';

       if(!obj.segs){
           try {
               args.onError();
           } catch(e1) {}
       }
       try {
           if (obj.segs[0]) {
               place = obj.segs[0].st.replace(/&#(\d+);/g, function(whole, paren1) { return String.fromCharCode(+paren1); });
           }
           var r = { lon: this._ll.lon, lat: this._ll.lat, address: place };
           args.onComplete([r]);
       } catch(e2) {}
   },

   /**
    * onTimeout
    */
   _onTimeout: function()
   {
       if (this.onErrorCallback) {
           try {
               this.onErrorCallback();
           } catch(e1) {}
       }
       this.onCompleteCallback = this.onErrorCallback = null;
   }
});