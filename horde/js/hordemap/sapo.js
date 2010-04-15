/**
 * SAPO specific version of HordeMap javascript.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

/* Include the sapo maps javascript */
document.write('<script src="http://js.sapo.pt/Bundles/SAPOMapsAPI-1.1.js"></script>');



var isIE = (/MSIE (\d+\.\d+);/.test(navigator.userAgent));

HordeMap.Map.SAPO = Class.create({
    map: null,
    mapMarker: null,
    markers: [],
    markersLayer: null,

    selectedMarker: null,
    addingAMarker: false,
    imgDiv: null,
    updateImgDivFunc: null,
    cancelFunc: null,
    firstUpdate: true,
    markerOut: true,
    lockMarker: false,
    panTimer: null,
    borders: null,
    BORDER_TOP: 120,
    BORDER_LEFT: 25,
    BORDER_BOTTOM: 5,
    BORDER_RIGHT: 10,
    MOVE_X: 100,
    MOVE_Y: 100,
    TIMER_INTERVAL: 100,
    search: null,
    navigation: null,
    sliderOpen: 0,
    markAction: 0,

// COMPARE WITH: /fasmounts/webmail/www/mai.webmail.labs.sapo.pt/webmail/sapoMapas/js/sapoMapasWidget.js :>> function init(){
    initialize: function(opts)
    {

            var o = {
                    showLayerSwitcher: true,
                    delayed: false,
                    layers: [],
                    fallThrough: false
            };
    
            this.opts = Object.extend(o, opts || {});
         

            this.borders = {left:10, top:50, right:10, bottom:10};

            this.map = new SAPO.Maps.Map('kronolithEventMap',this.opts);

            this.map.zoomTo(5);

            if (!window.isIE) {
                this.map.setMapCenter(new OpenLayers.LonLat(-16.652530624659608, 37.288788193170646));
            }

            this.map.addControl(new SAPO.Maps.Control.MapType2());
            this.navigation = new SAPO.Maps.Control.Navigation2()
            this.map.addControl(this.navigation);

            this.markersLayer = new SAPO.Maps.Markers('Markers');
            this.map.addMarkers(this.markersLayer);

            OpenLayers.Util.extend(this, opts);

            this.search = new SAPO.Maps.Search(this.map, 'kronolithEventResults', { borders : {left: 260, top:40} });
            this.search.registerEvent('completed',this, this._onCompleted);
            this.search.registerEvent('timeout',this, this._onTimeOut);
            this.search.registerEvent('error',this, this._onError);
            this.search.registerEvent('selected', this, this._onSelected);

            this.closeSliderDiv = $('closeSlider');
            this.closeSliderDiv.onclick = this.removeSearch.bindAsEventListener(this, 'click');
            this.openSliderDiv = $('openSlider');
            this.openSliderDiv.onclick = this.openSlider.bindAsEventListener(this, 'click');
    },

	searchPOIs: function(evt) {

		OpenLayers.Event.observe($('kronolithEventResults'), 'click', OpenLayers.Event.stop);
		OpenLayers.Event.observe($('kronolithEventResults'), 'dblclick', OpenLayers.Event.stop);
		OpenLayers.Event.observe($('kronolithEventResults'), 'mousemove', OpenLayers.Event.stop);

		this.clearResults();
		var query = $F('kronolithEventLocation');
		if(query.length == 0) return false;
		this.lastSearch=query;
		this.sliderOpen = 0;
		this.openSlider('click');
		this.search.search(query, { showDashboard : false, categorizedSearch: true, resultsPerPage : 3, allowPaging: true } );
	},

   clearResults: function(){
	   if (this.search == null) return;
	   this.search.cancel();
	   this.search.clear();
    },


   _onSelected: function (search) {
	   var index = search.selectedIdx;
	   document.getElementById('kronolithEventLocationLon').value = search.pois[0].POIs[index].Longitude;
	   document.getElementById('kronolithEventLocationLat').value = search.pois[0].POIs[index].Latitude;
	},

   _onCompleted: function (pois)
   {
	   if(!this.search.getTotalResults()) {
		   KronolithCore.showNotifications([ { type: 'horde.warning', message: 'O SAPO Mapas ainda não suporta endereços fora de Portugal. Por favor confirme se digitou correctamente o endereço.' } ]);
	   }
   },


   openSlider: function (evt)
   {
	 if(this.sliderOpen)
	 {
		  evt = evt || window.event;
		  OpenLayers.Event.stop(evt);

		  this.navigation.div.style.left = '10px';
		  $('kronolithEventResults').style.marginLeft='-202px';
		  this.sliderOpen = 0;
	 }
	 else
	 {
		 if (this.navigation == null) return;
		 this.navigation.div.style.left = '220px';
		 $('kronolithEventResults').style.marginLeft='0px';
		 $('kronolithEventResults').style.display='block';
		 this.sliderOpen = 1;
	 }
   },

   removeSearch: function (evt)
   {
	 this.navigation.div.style.left = '10px';
	 evt = evt || window.event;
	 OpenLayers.Event.stop(evt);
	 this.sliderOpen = 0;
	 $('kronolithEventResults').style.display='none';
	 this._removeMarkers();
	 this.clearResults();
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
        // fix navigation bar
        this.clearResults();
        var resultDiv = $w('category_results');
        var titleDiv = $w('search_title_header');
        if(resultDiv.lenght > 0) { resultDiv.remove(); }
        if(titleDiv.lenght > 0) { titleDiv.remove(); }

        if(this.map==null) return;
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

    addMarker: function(lonlat,o,e){

        if (!lonlat) {
            this.markAction = 1;
            if (this.addingAMarker) {
                return;
            }

            this._removeMarkers();

            this.map.events.register('mouseout', this, this.mouseout);
            this.map.events.register('mousemove', this, this.mousemove);
            this.map.events.register('click', this, this.markerAnchored);

            this.addDivImg();
            this.addingAMarker = true;
        } else if (this.markAction == 1) {

           if ( window.isIE) {
                var marker = new SAPO.Maps.Marker(lonlat, {draggable: true});
            } else {
                var marker = new SAPO.Maps.Marker(new OpenLayers.LonLat(lonlat.lon, lonlat.lat), o || {});
            }
            
            this.markersLayer.addMarker(marker);
            this.markers.push(marker);

            
            if (!window.isIE) {
               this.setCenter(lonlat,this.map.getZoom());
            }
            
            e = e || {};
            e.context = e.context || marker;
            if (e.click) { marker.registerEvent('click', e.context, e.click) };
            if (e.mouseover) { marker.registerEvent('mouseover', e.context, e.mouseover) };
            if (e.mouseout) { marker.registerEvent('mouseout', e.context, e.mouseout) };
            if (e.drag) { marker.registerEvent('drag', e.context, e.drag) };
            if (e.dragend) { marker.registerEvent('dragend', e.context, e.dragend) };
            if (e.dragstart) { marker.registerEvent('dragstart', e.context, e.dragstart) };
        }

    },


    cancel: function(evt){
        if((this.addingAMarker == true) || (!evt && this.addingAMarker) || (evt && evt.keyCode == 27) || (evt && evt.type === 'mouseup')){
            e = e || {};
            e.context = e.context || marker;
            if (e.click) { marker.registerEvent('click', e.context, e.click) };
            if (e.mouseover) { marker.registerEvent('mouseover', e.context, e.mouseover) };
            if (e.mouseout) { marker.registerEvent('mouseout', e.context, e.mouseout) };
            if (e.drag) { marker.registerEvent('drag', e.context, e.drag) };
            if (e.dragend) { marker.registerEvent('dragend', e.context, e.dragend) };
            if (e.dragstart) { marker.registerEvent('dragstart', e.context, e.dragstart) };

            this.removeDivImg();
            this.addingAMarker = false;
        }
    },


    addDivImg: function(){
        var div = document.createElement('div');
        var img = document.createElement('img');
        img.src = 'http://imgs.sapo.pt/fotos_gis/mapas_api/v1.1/Markers/new/pin.png';
        img.width = 22;
        img.height = 28;

        div.style.position = 'absolute';
        div.style.zIndex = '1000';
        div.appendChild(img);
        if(SAPO.Maps.Utils.checkIE6()){
            div.className += ' search_item_marker_ie6';
            img.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled='true',src='" + img.src + "')";
        }

        this.updateImgDivFunc = OpenLayers.Function.bindAsEventListener(this.updateDivImg, this);
        this.cancelFunc = OpenLayers.Function.bindAsEventListener(this.removeMarker, this);

        OpenLayers.Event.observe(document.body, 'mousemove', this.updateImgDivFunc, true);
        OpenLayers.Event.observe(document.body, 'keydown', this.cancelFunc, true);
        this.map.events.register('singlerightclick', this, this.cancelFunc.bindAsEventListener(this));

        this.imgDiv = div;

    },

    removeDivImg: function(){
        if (this.imgDiv == null) return;
        this.imgDiv.parentNode.removeChild(this.imgDiv);
        OpenLayers.Event.stopObserving(document.body, 'mousemove', this.updateImgDivFunc, true);
        OpenLayers.Event.stopObserving(document.body, 'keydown', this.cancelFunc, true);
        this.map.events.unregister('singlerightclick', this, this.cancelFunc.bindAsEventListener(this));

        this.firstUpdate = true;
        this.markerOut = true;
        this.imgDiv = null;
        this.updateImgDivFunc = null;
        this.cancelFunc = null;
    },

    updateDivImg: function(evt){
        if(this.lockMarker){return;}
        if(this.firstUpdate) {
            document.body.appendChild(this.imgDiv);
            this.firstUpdate = false;
        }

        this.imgDiv.style.top = (evt.clientY - 38) + 'px';
        this.imgDiv.style.left = (evt.clientX - 12) + 'px';
    },

    /**
     * Event handle called when the user moves the mouse out of the map.
     * Drag the map.
     */

    mouseout: function(evt){
         this.mousemove(evt);
    },


    /**
     * Event handle called when the user is moving the mouse over the map.
     * Update the marker position
     */
    mousemove: function(evt){

        var size = this.map.size;
        if(evt.xy.x <= this.borders.left || evt.xy.y <= (this.borders.top - 30)){
            //lock marker and pan map up
            this.panUp(evt.xy);
        }else{
            if(evt.xy.x + this.borders.right >= size.w || evt.xy.y + this.borders.bottom >= size.h){
                //lock marker and pan map down
                this.panDown(evt.xy);
            }
            else{
                //if panning stop it
                if(this.panTimer){
                    window.clearInterval(this.panTimer);
                    this.lockMarker = false;
                    this.panTimer = null;
                }
            }
        }

    },

    panUp: function(xy){
        //first time the mouse enters on the map
        if(this.panTimer){return;}
        if(this.markerOut){
            this.markerOut = false;
            return;
        }
        this.lockMarker = true;

        var x = 0;
        var y = 0;
        if(xy.x <= this.borders.left && xy.y <= (this.borders.top - 30)){
            x = -1 * this.MOVE_X;
            y = -1 * this.MOVE_Y;
        }else{
            if(xy.x <= this.borders.left){
                x = -1 * this.MOVE_X;
            }else{
                y = -1 * this.MOVE_Y;
            }
        }

        var map = this.map;
        //set a timeout to pan the map
        this.panTimer = window.setInterval(function(){ map.pan(x, y); }.bind(this), 100);
    },


    panDown: function(xy){
        //first time the mouse enters on the map
        if(this.panTimer){return;}
        if(this.markerOut){
            this.markerOut = false;
            return;
        }
        this.lockMarker = true;

        var size = this.map.size;

        var x = 0;
        var y = 0;
        if(xy.x + this.borders.right >= size.w && xy.y + this.borders.bottom >= size.h){
            x = this.MOVE_X;
            y = this.MOVE_Y;
        }else{
            if(xy.x + this.borders.right >= size.w){
                x = this.MOVE_X;
            }else{
                y = this.MOVE_Y;
            }
        }

        var map = this.map;
        //set a timeout to pan the map
        this.panTimer = window.setInterval(function(){ map.pan(x, y); }.bind(this), 100);
    },



    /**
     * Event handle called when the marker, is handled on the map.
     */
    markerAnchored: function(evt){


        this.map.events.unregister('mouseout', this, this.mouseout);
        this.map.events.unregister('mousemove', this, this.mousemove);
        this.map.events.unregister('click', this, this.markerAnchored);

        this.removeDivImg();
        this.addingAMarker = false;

        var lonlat = this.map.getLonLatFromContainerPixel(evt.xy);
        if (this.opts.mapClick) { this.opts.mapClick({ lonlat: lonlat }); }

        this.markAction = 0;
    },

    removeMarker: function(marker){
        this.markersLayer.removeMarker(marker);
        this.markers.splice(marker.idx, 1);
    },

    closeOpenedPopup: function(){
        if(this.selectedMarker){
            this.selectedMarker.closePopup();
            this.selectedMarker = null;
        }
    },

    _removeMarkers: function(){
        this.markersLayer.removeMarkers();
        this.markers.splice(0);
    },

    /**
     * API Method
     * This method recevives a string with the object state, and recovers all the state.
     *
     * The state is a string in a following format:
     *  lon,lat,opened,title,desc@lon,lat,opened,title,desc
     * @param {String} state
     */
    setState: function(state){
        var markers = state.split('@');

        var m = false;
        var mInfo = false;
        var lat = false;
        var lon = false;
        var opened = false;
        var title = false;
        var desc = false;


        for(var i = 0; i< markers.length; ++i){
            mInfo = markers[i].split(',');
            try {
                lon = Number(mInfo[0]);
                lat = Number(mInfo[1]);
                opened = Number(mInfo[2]);
                title = mInfo.length > 3? mInfo[3] : '';
                desc = mInfo.length > 4? mInfo[4] : '';

                m = new SAPO.Maps.Marker(new OpenLayers.LonLat(lon, lat), {draggable: true});
                
                if (window.isIE) {
                    var lonlat = false;
                    lonlat.lon = lon;
                    lonlat.lat = lat;
                    m = new SAPO.Maps.Marker(lonlat, {draggable: true});
                } else {
                    m = new SAPO.Maps.Marker(new OpenLayers.LonLat(lon, lat), {draggable: true});
                }
                
                m.title = decodeURIComponent(title);
                m.description = decodeURIComponent(desc);
                m.idx = markers.length;

                this.markersLayer.addMarker(m);
                this.markers.push(m);

            }catch(e){}
        }
    },

    getState: function(){
        var str = '';

        m = false;
        var opened = false;
        var lonlat = false;
        var title = false;
        var desc = false;
        for(var i=0; i< this.markers.length; ++i){
            m = this.markers[i];
            lonlat = m.getLonLat();
            opened = m.hasOpenedPopup()? 1 : 0;
            title = encodeURIComponent(encodeURIComponent(m.title));
            desc = encodeURIComponent(encodeURIComponent(m.description));
            str += lonlat.lon + ',' + lonlat.lat + ',' + opened + ',' +
                    title + ',' + desc;

            if(i < this.markers.length - 1){
                str += '@';
            }
        }

        return str;
    },

     getMapsURL: function() {
         var coord = this.map.getMapCenter();
         var url ='http://mapas.sapo.pt/?ll=' +coord.lat +',' +coord.lon +'&z='+this.map.getZoom();

         if(!this.search == null) {
             var page = this.search.getCurrentPage(this.search.openedZone);
             var selectedIndex = this.search.getSelectedIndex();
             if(page >0 ) {
                 url += '&q='+encodeURI(this.lastSearch)+','+page+','+selectedIndex+','+encodeURI(this.search.openedZone)+',true';
             }
         }
         var zz = this.getState();
         if(zz) {
             url += '&mks='+zz;
         }

         switch(this.map.baseLayer.layername) {
            case 'map' : url += '&t=m'; break;
            case 'hybrid' : url += '&t=h'; break;
            case 'satellite' : url += '&t=s'; break;
            case 'terrain' : url += '&t=t'; break;
         }

         return url;
     },

     getImageURL: function() {
         var coord = this.map.getMapCenter();
         var ll = 'll='+coord.lat+','+coord.lon;
         var mks = this.getState();
         mks = (mks) ? mks : '';
         if(this.search.getSelectedIndex() != null) {
             var selectedSearch = this.search.getMarker(this.search.getSelectedIndex());
             if(mks != '') { mks+='@'}
             mks+= selectedSearch.getLonLat().lon+','+ selectedSearch.getLonLat().lat+ ',1,'+encodeURIComponent(selectedSearch.html.getElementsByTagName('p')[0].innerHTML)+',';
         }

         if(mks != '') { mks='&mks='+ mks; }
         var url = 'http://services.sapo.pt/Maps/GetMapByLinkParameters?'+ll+'&z='+(this.map.getZoom()-1) +'&width=340&height=225'+mks+'&t=' + this.map.baseLayer.layername;
         return url;
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

       if (KronolithCore.map.markAction == 0 ) {
          KronolithCore.map.searchPOIs(true);
       }

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
           } catch(e1) { }
       }
       try {
           $F('kronolithEventGeo');
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


/* auxiliary Event */

    function addEvent(obj, evType, fn) {
        try {

            if (obj.addEventListener){
                obj.addEventListener(evType, fn, false);
               return true;
            } else if (obj.attachEvent) {
                var r = obj.attachEvent("on"+evType, fn);
                return r;
            } else {
                return false;
            }
        } catch (e) {
            alert("addEvent Element error:" + obj + "\nError:\n" + e);
        }
    }



});


/*  element.setAttribute('style', ...) workaround for IE */
 function rzCC(s){
   // thanks http://www.ruzee.com/blog/2006/07/\
   // retrieving-css-styles-via-javascript/
   for(var exp=/-([a-z])/; 
       exp.test(s); 
       s=s.replace(exp,RegExp.$1.toUpperCase()));
   return s;
 }

 function _setStyle(element, declaration) {
   if (declaration.charAt(declaration.length-1)==';')
     declaration = declaration.slice(0, -1);
   var k, v;
   var splitted = declaration.split(';');
   for (var i=0, len=splitted.length; i<len; i++) {
      k = rzCC(splitted[i].split(':')[0]);
      v = splitted[i].split(':')[1];
      eval("element.style."+k+"='"+v+"'");

   }
 }

function addSapoMapasUI() {
    /* template in: /horde/kronolith/templates/index/edit.inc */
    if (document.getElementById('b_adicionar') == undefined) {
        var tabmap = document.getElementById('kronolithEventTabMap');
        var mark_in_map_link = document.createElement('button');
        mark_in_map_link.className = 'b_adicionar';
        mark_in_map_link.setAttribute('id', 'b_adicionar');
        mark_in_map_link.setAttribute('type', 'button');
/*
        var gesture = '';
        if (mark_in_map_link.addEventListener){
            gesture = 'mousedown';                
        } else if (mark_in_map_link.attachEvent) {
            gesture = 'onmousedown';
        }
        mark_in_map_link.setAttribute(gesture,'KronolithCore.map.addMarker();');
*/

//        var isIE = (/MSIE (\d+\.\d+);/.test(navigator.userAgent));
//        mark_in_map_link.setAttribute((isIE ? 'onmousedown' : 'mousedown'),'KronolithCore.map.addMarker();');
/*
        if (isIE) {
            mark_in_map_link.setAttribute('onmousedown','KronolithCore.map.addMarker();');
        } else {
            mark_in_map_link.setAttribute('mousedown','KronolithCore.map.addMarker();');
        }
*/
        mark_in_map_link.innerHTML = 'Adicionar Marcador';

        var action_container = document.createElement('div');
        action_container.className = 'action_container';
        tabmap.appendChild(action_container);
        action_container.appendChild(mark_in_map_link);
    }

    if (document.getElementById('kronolithEventResults') == undefined) {
      var eventmap = document.getElementById('kronolithEventMap');
      eventmap.className = 'mapa';
      // eventmap.setAttribute('style', 'width:100%; height:270px;');
      _setStyle(eventmap, 'width:100%; height:270px');              // Sapo Mapas: Mandatory width and height !!!!!!!

      var resultBox = document.createElement('div');
      resultBox.setAttribute('id', 'kronolithEventResults');
      resultBox.className = 'slider';

      var closeSlider = document.createElement('div');
      closeSlider.className = 'sliderDoorTop';
      closeSlider.setAttribute('id', 'closeSlider');

      var openSlider = document.createElement('div');
      openSlider.className = 'sliderDoor';
      openSlider.setAttribute('id', 'openSlider');

      var _a = document.createElement('a');
      var _span = document.createElement('span');
      var slidetxt = document.createTextNode('slide');

      eventmap.appendChild(resultBox);
      resultBox.appendChild(closeSlider);
      resultBox.appendChild(openSlider);
      openSlider.appendChild(_a).appendChild(_span).appendChild(slidetxt);
    }

// alert(document.getElementById('kronolithEventTabMap').innerHTML);

      var openSlider = document.createElement('div');
      openSlider.className = 'sliderDoor';
      openSlider.setAttribute('id', 'openSlider');


    // On KeyPress Search Fix
    function ie8SafePreventEvent(e){
        if (e.preventDefault){
            e.preventDefault();
        } else {
            if (e.stop) { e.stop(); }
        }

      eventmap.appendChild(resultBox);
      resultBox.appendChild(closeSlider);
      resultBox.appendChild(openSlider);
      openSlider.appendChild(_a).appendChild(_span).appendChild(slidetxt);
    }

    addEvent(document.getElementById('kronolithEventLocation'), 'keydown',
        function(event){

            if (event.which || event.keyCode) {
                if ((event.which == 13) || (event.keyCode == 13)) {
                    ie8SafePreventEvent(event);
                    KronolithCore.ensureMap();
                    KronolithCore.geocode(document.getElementById('kronolithEventLocation'));
                } else if ((event.which == 27) || (event.keyCode == 27)) {
                    ie8SafePreventEvent(event);
                    document.getElementById('kronolithEventLocation').value = "";
                    document.getElementById('kronolithEventLocation').focus();
                }

            } else {
                ie8SafePreventEvent(event);
            }
        }
    );






    addEvent(document.getElementById('b_adicionar'), 'mousedown',
        function(event){
            KronolithCore.map.addMarker();
        }
    );





}

function init(){

    // quit if this function has already been called
    if (arguments.callee.done) return;
    // flag this function so we don't do the same thing twice
    arguments.callee.done = true;

    if (document.getElementById('kronolithEventResults') == undefined) {
       addSapoMapasUI();
    }

}

//    window.onload = init();

/* for Mozilla/Opera9 */
if (document.addEventListener) {
  document.addEventListener("DOMContentLoaded", init, false);
}

/* for Internet Explorer */
/*@cc_on @*/
/*@if (@_win32)
  document.write("<script id=__ie_onload defer src=javascript:void(0)><\/script>");
  var script = document.getElementById("__ie_onload");
  script.onreadystatechange = function() {
    if (this.readyState == "complete") {
      init(); // call the onload handler
    }
  };
/*@end @*/

/* for Safari */
if (/WebKit/i.test(navigator.userAgent)) { // sniff
  var _timer = setInterval(function() {
    if (/loaded|complete/.test(document.readyState)) {
      init(); // call the onload handler
    }
  }, 10);
}

/* for other browsers */
window.onload = init;
