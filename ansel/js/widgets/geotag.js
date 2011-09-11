AnselGeoTagWidget = Class.create({
    _bigMap: null,
    _smallMap: null,
    _images: null,
    tilePrefix: 'imagetile_',
    locationId: 'ansel_locationtext',
    coordId: 'ansel_latlng',
    relocateId: 'ansel_relocate',
    deleteId: 'ansel_deleteGeotag',

    /**
     * Const'r.
     *
     * Required opts:
     *   viewType [Gallery|Image]
     *    relocateUrl [Url for relocate popup]
     *    relocateText [Localized text]
     *    deleteGeotagText [Localized text]
     *    hasEdit [boolean - do we have PERMS_EDIT?]
     *    updateEndPoint [AJAX endpoint for updating image data]
     */
    initialize: function(imgs, opts) {
         var o = {
            smallMap: 'ansel_map_small',
            mainMap:  'ansel_map',
            geocoder: 'None',
            calculateMaxZoom: true,
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
        this._bigMap = AnselMap.ensureMap('ansel_map');
        this.geocoder = new HordeMap.Geocoder[this.opts.geocoder](this._bigMap.map, 'ansel_map');
        this._smallMap = AnselMap.ensureMap('ansel_map_small');
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

            // @TODO: finish this - do we still want to do this for gallery view?
            if (this._images[i].markerOnly) {
                (function() {
                    var p = this._images[i];
                    this.getLocation(p);
                }.bind(this))();
            }

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

        this._smallMap.setCenter({'lat': this._images[0].image_latitude, 'lon': 0}, 1);
//                this.map.getLocationCallback_ = this.map.getLocationCallback;
//                this.map.getLocationCallback = function(points, marker) {
//                    this.map.getLocationCallback_(points, marker, (typeof points.NoUpdate == 'undefined'));
//                }.bind(this);
//                this.map.addPoints(this.images);
//                this.map.display();
    },

    getLocation: function(p) {
        if (p.image_location.length > 0) {
            // Have cached reverse geocode results
            var r = [ { address: p.image_location, lat: p.image_latitude, lon: p.image_longitude } ];
            this.getLocationCallback(p, false, r);
        } else {
            this.geocoder.reverseGeocode(
                { lat: p.image_latitude, lon: p.image_longitude },
                this.getLocationCallback.bind(this).curry(p, true),
                this.onError.bind(this));
        }
    },

    /**
     * callback for reverse geocode call
     *
     * @param object i   The image hash
     * @param boolean u  Update the image location in the backend
     * @param object r   The AJAX response
     */
    getLocationCallback: function(i, u, r)
    {
        // Update image view for current image
        if (i.markerOnly) {
            if (this.locationId) {
                $(this.locationId).update(r[0].address);
            }
            if (this.coordId) {
                $(this.coordId).update(this._point2Deg({ lat: r[0].lat, lon: r[0].lon }));
            }
            if (this.relocateId) {
                $(this.relocateId).update(this._getRelocateLink(i.image_id));
            }

            if (this.deleteId) {
                $(this.deleteId).update(this._getDeleteLink(i.image_id));
            }
        }

        // Save the results?
        if (u) {
            new Ajax.Request(this.opts['updateEndpoint'] + "/action=location/post=values",
                {
                    method: 'post',
                    parameters: { "values": "location=" + encodeURIComponent(r[0].address) + "/img=" + i.image_id }
                }
            );
        }
    },

    onError: function(r)
    {
        console.log(r);
    },

    _getRelocateLink: function(iid) {
        if (this.opts.hasEdit) {
            var a = new Element('a', {
                href: this.opts.relocateUrl + '?image=' + iid }
            ).update(this.opts.relocateText);

            a.observe('click', function(e) {
                Horde.popup({
                    url: this.options.relocateUrl,
                    params: 'image=' + iid, width: 750, height: 600
                });
                e.stop();
            }.bind(this));

            return a;
        } else {
            return '';
        }
    },

    _getDeleteLink: function(iid) {
        var x = new Element('a', {
            href: this.opts.relocateUrl + '?image=' + iid}
        ).update(this.opts.deleteGeotagText);

        x.observe('click', function(e) {
            this.opts.deleteGeotagCallback();
            e.stop();
        }.bindAsEventListener(this));
        return x;
    },

    _point2Deg: function(ll) {
         function dec2deg(dec, lat)
         {
             var letter = lat ? (dec > 0 ? "N" : "S") : (dec > 0 ? "E" : "W");
             dec = Math.abs(dec);
             var deg = Math.floor(dec);
             var min = Math.floor((dec - deg) * 60);
             var sec = (dec - deg - min / 60) * 3600;
             return deg + "&deg; " + min + "' " + sec.toFixed(2) + "\" " + letter;
         }

         return dec2deg(ll.lat, true) + " " + dec2deg(ll.lon);
    },

});