AnselGeoTagWidget = Class.create({
    _bigMap: null,
    _smallMap: null,
    _images: null,

    initialize: function(imgs, opts) {
         var o = {
            smallMap: 'ansel_map_small',
            mainMap:  'ansel_map',
            //viewType: '{$viewType}',
            //relocateUrl: '{$url}',
            //relocateText: '{$rtext}',
            //deleteGeotagText: '{$dtext}',
            //hasEdit: {$permsEdit},
            calculateMaxZoom: true,
            //updateEndpoint: '{$impleUrl}',
            //deleteGeotagCallback: this.deleteLocation
        };
        this._images = imgs;
        this.opts = Object.extend(o, opts || {});
    },

    // setLocation: function(lat, lng)  {
    //     var params = { "values": "img={$image_id}/lat=" + lat + "/lng=" + lng };

    //     var url = "{$impleUrl}";
    //     new Ajax.Request(url + "/action=geotag/post=values", {
    //         method: 'post',
    //         parameters: params,
    //         onComplete: function(transport) {
    //              if (typeof Horde_ToolTips != 'undefined') {
    //                  Horde_ToolTips.out();
    //              }
    //              if (transport.responseJSON.response == 1) {
    //                 var w = new Element('div');
    //                 w.appendChild(new Element('div', {id: 'ansel_map'}));
    //                 var ag = new Element('div', {'class': 'ansel_geolocation'});
    //                 ag.appendChild(new Element('div', {id: 'ansel_locationtext'}));
    //                 ag.appendChild(new Element('div', {id: 'ansel_latlng'}));
    //                 ag.appendChild(new Element('div', {id: 'ansel_relocate'}));
    //                 ag.appendChild(new Element('div', {id: 'ansel_deleteGeotag'}));
    //                 w.appendChild(ag);
    //                 w.appendChild(new Element('div', {id: 'ansel_map_small'}));
    //                 $('ansel_geo_widget').update(w);
    //                 this.images.unshift({image_id: {$image_id}, image_latitude: lat, image_longitude: lng, image_location:'', markerOnly:true});
    //                 this.doMap();
    //              }
    //          }.bind(this)
    //     });
    // },

    // deleteLocation: function() {
    //     var params = {"values": "img={$image_id}" };
    //     var url = "{$impleUrl}";
    //     new Ajax.Request(url + "/action=untag/post=values", {
    //         method: 'post',
    //         parameters: params,
    //         onComplete: function(transport) {
    //             if (transport.responseJSON.response == 1) {
    //                 $('ansel_geo_widget').update(transport.responseJSON.message);
    //             }
    //         }
    //     });

    // },

    doMap: function() {
        AnselMap.ensureMap('ansel_map');
        var m =AnselMap.ensureMap('ansel_map_small');
        for (var i = 0; i < this._images.length; i++) {
            AnselMap.placeMapMarker(
                'ansel_map',
                {
                    'lat': this._images[i].image_latitude,
                    'lon': this._images[i].image_longitude
                },
                true,
                null,
                (!this._images[i].markerOnly) ? this._images[i].icon : null
            );
            AnselMap.placeMapMarker(
                'ansel_map_small',
                {
                    'lat': this._images[i].image_latitude,
                    'lon': this._images[i].image_longitude
                },
                false,
                null
            );
        }
         m.setCenter({'lat': this._images[0].image_latitude, 'lon': 0}, 1);
//                this.map.getLocationCallback_ = this.map.getLocationCallback;
//                this.map.getLocationCallback = function(points, marker) {
//                    this.map.getLocationCallback_(points, marker, (typeof points.NoUpdate == 'undefined'));
//                }.bind(this);
//                this.map.addPoints(this.images);
//                this.map.display();
    }
});