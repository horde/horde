/**
 * Google maps implementation for Ansel
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
var Ansel_GMap = Class.create();

Ansel_GMap.prototype = {
    // Main google map handle
    mainMap: undefined,

    // Smaller overview map handle
    smallMap: undefined,

    // Tinymarker icons...
    tI: undefined,
    tIO: undefined,

    // GLatLngBounds obejct for calculating proper center and zoom
    bounds: undefined,

    // Geocoder
    geocoder: undefined,

    // MarkerManager, if we are browsing the map.
    // Note we need <script src="http://gmaps-utility-library.googlecode.com/svn/trunk/markermanager/release/src/markermanager.js">
    manager: undefined,

    // Can override via options array
    // Pass these in options array as empty string if you do not have them
    // on your view
    tilePrefix: 'imagetile_',
    locationId: 'ansel_locationtext',
    coordId: 'ansel_latlng',
    relocateId: 'ansel_relocate',
    deleteId: 'ansel_deleteGeotag',
    maxZoom: 15,
    defaultZoom: 15,
    options: {},

    // Array of GOverlays (GMarker or Ansel_GOverlay objects)
    points: [],

    // const'r
    // options.smallMap = [id: 80px];
    //        .mainMap [id: xx]
    //        .viewType (Gallery, Image, Edit)
    //        .relocateUrl base url to the edit/relocate page
    //        .relocateText localized text for the relocate link
    //        .deleteGeotagText localized text for delete geotag link
    //        .deleteGeotagCallback js callback function to be called after
    //                              deletion is successful

    //        .clickHandler - optional callback to handle click events on the mainMap
    //        .hasEdit - Has Horde_Perms::EDIT on the image
    //        .calculateMaxZoom - call Google's getMaxZoomAtLatLng() method to
    //                            avoid autozooming in to a level with no detail.
    //                            Performance penalty because we make another
    //                            round trip to google's service and lately it
    //                            appears rather slow.
    //        .updateEndpoint     URL to imple.php
    initialize: function(options) {
        // Use the manager by default.
        if (typeof options.useManager == 'undefined') {
            options.useManager = true;
        }
        this.mainMap = new GMap2($(options.mainMap));
        this.mainMap.setMapType(G_HYBRID_MAP);
        this.mainMap.setUIToDefault();
        this.bounds = new GLatLngBounds();
        this.geocoder = new GClientGeocoder();
        this.options = options;
        if (options.tilePrefix) {
            this.tilePrefix = options.tilePrefix;
        }

        if (typeof options.calculateMaxZoom == 'undefined') {
            options.calculateMaxZoom = true;
        }

        if (options.smallMap) {
            this.smallMap = new GMap2($(options.smallMap));
            var cUI = this.smallMap.getDefaultUI();
            cUI.controls.menumaptypecontrol = false;
            this.smallMap.setUI(cUI);

            // Create our "tiny" marker icon
            // We should copy these locally once this is fleshed out...
            this.tI = new GIcon();
            this.tI.image = "http://labs.google.com/ridefinder/images/mm_20_red.png";
            this.tI.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
            this.tI.iconSize = new GSize(12, 20);
            this.tI.shadowSize = new GSize(22, 20);
            this.tI.iconAnchor = new GPoint(6, 20);
            this.tI.infoWindowAnchor = new GPoint(5, 1);
            this.tIO = { icon:this.tI };
        }

        // Clean up
        document.observe('unload', function() {GUnload();});
    },

    /**
     * Adds a set of points to the map. Each entry in the points array should
     * contain:
     *   image_latitude,image_longitude
     *   (optional)markerOnly - Don't add thumbnails or event handlers
     *
     * @param array
     * @param minZoom  at what minimum zoom level should this set of points be
     *                 displayed? Ignored if not using the MarkerManager
     */
    addPoints: function(points, minZoom) {
        var l = points.length;
        for (var i = 0; i < l; i++) {
            var ll = new GLatLng(parseFloat(points[i].image_latitude), parseFloat(points[i].image_longitude));
            this.bounds.extend(ll);
            if (points[i].markerOnly == true) {
                // We only support draggable GMarkers, not custom overlays.
                if (points[i].draggable) {
                    var mO = {draggable: true};
                    var marker = new GMarker(ll, mO);
                    GEvent.addListener(marker, "drag", function(ll) {
                        $(this.coordId).update(this._point2Deg(ll));
                    }.bind(this));
                    GEvent.addListener(marker, "dragend", function(ll) {
                        this.geocoder.getLocations(ll, function(address) {
                                this.getLocationCallback(address, new GMarker(ll));
                                }.bind(this));
                        }.bind(this));
                } else {
                    // This is the single marker for the current image in the image view.
                    // Make sure we have a high enough zIndex value for it.
                    var marker = new GMarker(ll, {zIndexProcess: function(marker) {return GOverlay.getZIndex(-90);}});
                }
            } else {
                var marker = new anselGOverlay(ll, points[i]);
            }
            // Click handlers only apply to our custom GOverlay.
            if (!points[i].markerOnly && !this.options.viewType == 'Block') {
                (function() {
                    var p = points[i];
                    GEvent.addDomListener(marker.div_, 'click', function() {
                        a = $$('#' + this.tilePrefix + p.image_id + ' a')[0];
                        if (!a.onclick || a.onclick() != false) {
                            location.href = a.href;
                        }
                    }.bind(this));}.bind(this))();
            }
            // extend the GOverlay with our image data too.
            marker.image_data = points[i];
            this.points.push(marker);

            // Only put the current image on the small map if we are in the
            // Image view.
            if (this.options.smallMap &&
                (this.options.viewType != 'Image' || points[i].markerOnly)) {
                var marker2 = new GMarker(ll, this.tIO);
                this.smallMap.addOverlay(marker2);
            }
        }

        if (this.options.viewType == 'Gallery' || this.options.viewType == 'Block') {
            if (this.options.calculateMaxZoom) {
                this.mainMap.getCurrentMapType().getMaxZoomAtLatLng(this.bounds.getCenter(), function(response) {
                    if (response.status != 200) {
                        var zl = Math.min(this.mainMap.getBoundsZoomLevel(this.bounds) - 1, this.maxZoom);
                    } else {
                        var zl = Math.min(this.mainMap.getBoundsZoomLevel(this.bounds) - 1, Math.min(this.maxZoom, response.zoom - 1));
                    }
                    this._mapSetCenter(zl);
                    this._managerSetup(minZoom);
                }.bind(this));
            } else {
                this._mapSetCenter(Math.min(this.mainMap.getBoundsZoomLevel(this.bounds) - 1, this.maxZoom));
                this._managerSetup(minZoom);
            }
        } else {
            // Not a Gallery View...
            this._mapSetCenter(Math.min(this.mainMap.getBoundsZoomLevel(this.bounds) - 1, this.maxZoom), this.points[0].getLatLng());
            this._managerSetup(minZoom);
        }
    },

    /**
     * Helper method to set the map center and refresh the MarkerManager
     * if we are using one.
     *
     * @param integer zl  The zoom level to set the map at once it's centered.
     * @param integer mz  The minimum zoom level needed to display the currently
     *                    added points if using the MarkerManager.
     */
    _mapSetCenter: function(zl, mz, ctr) {
        if (!ctr) {
            ctr = this.bounds.getCenter();
        }
        this.mainMap.setCenter(ctr, zl);
        if (this.options.smallMap) {
            this.smallMap.setCenter(this.mainMap.getCenter(), 1);
        }
    },

    _managerSetup: function(mz) {
        // Can't instantiate a manager until after the GMap2 has had
        // setCenter() called, so we *must* do this here.
        if (this.options.useManager && this.manager == null) {
            this.manager = new MarkerManager(this.mainMap);
        }
        if (this.options.useManager) {
            if (mz == null) {
                mz = 0;
            }
            this.manager.addMarkers(this.points, mz);
            this.manager.refresh();
        }
    },

    /**
     * Display all points on the map. If we are using the MarkerManager, then
     * this function only obtains the reverse geocode data and (via the callback)
     * adds the event handlers to display the geocode data.  If we aren't using
     * the manager, this also adds the overlay to the map.
     */
    display: function() {
        var l = this.points.length;
        for (var i = 0; i < l; i++) {
            // Force closure on p
            (function() {
                var p = this.points[i];
                if (!this.options.useManager) {
                    this.mainMap.addOverlay(p);
                }
                // For now, only do this on the current Image in the image view
                // or for all images in Gallery view.
                if ((this.options.viewType != 'Block' && this.options.viewType != 'Image') || p.image_data.markerOnly) {
                    this.getLocations(p);
                }
            }.bind(this))();
        }

        if (this.options.clickHandler) {
            GEvent.addListener(this.mainMap, "click", this.options.clickHandler);
        }
    },

    /**
     * Custom getLocations method so we can check for our own locally cached
     * geodata first.
     */
    getLocations: function(p) {
        if (p.image_data.image_location.length > 0) {
            r = {Status: {code: 200}, Placemark: [{AddressDetails: {Accuracy: 4}, address:p.image_data.image_location}], NoUpdate: true};
            this.getLocationCallback(r, p, false);
        } else {
            this.geocoder.getLocations(p.getLatLng(), function(address) {this.getLocationCallback(address, p, true)}.bind(this));
        }
    },

    /**
     *  Callback to parse and attach location data the the points on the map.
     *  Adds event handlers to display the location data on mouseover. Also
     *  highlights the image tile (since these would only be called in gallery
     *  view) - need to use a seperate handler for that once we start storing
     *  reverse geocode data locally.
     *
     * @TODO: Is it worth the effort to define the callback in the page that
     *        is calling this to make this more OO-like? Maybe for H4 when I
     *        try to make this a more generic Google library??
     *
     */
    getLocationCallback: function(points, marker, update) {
        if (typeof update == 'undefined') { update = false;}
        if (points.Status.code != 200) {
            // Fake the data so we can at least update what we have
            points.Placemark = [{AddressDetails: {Accuracy: 0}, address: ''}];
            update = false;
        }

        if (marker.image_data) {
            var image_data = marker.image_data;
        } else {
            image_data = {};
        }

        for (var i = 0; i < points.Placemark.length; i++) {
            var place = points.Placemark[i];
            if (place.AddressDetails.Accuracy <= 4) {
                // These events handlers should only be fired on the Gallery
                // view for our special GOverlay objects (which already have
                // a mouseover/out handler to focus them).
                if (!image_data.markerOnly && this.options.viewType == 'Gallery' && this.locationId) {
                    GEvent.addDomListener(marker.div_, 'mouseover', function() {
                        $(this.locationId).update(place.address);
                        $$('#' + this.tilePrefix + image_data.image_id + ' img')[0].toggleClassName('image-tile-highlight');
                    }.bind(this));
                    GEvent.addDomListener(marker.div_, 'mouseout', function() {
                        $(this.locationId).update('<br />');
                        $$('#' + this.tilePrefix + image_data.image_id + ' img')[0].toggleClassName('image-tile-highlight');
                    }.bind(this));
                }

                // Cache the location locally?
                if (update) {
                    new Ajax.Request(this.options['updateEndpoint'] + "/action=location/post=values",
                                    {
                                        method: 'post',
                                        parameters: { "values": "location=" + encodeURIComponent(place.address) + "/img=" + image_data.image_id }
                                    }
                    );
                }
                // These handlers are for the image tiles themselves in the
                // Gallery view - to highlight our GOverlays on the map.
                if (this.options.viewType == 'Gallery') {
                    if (this.locationId) {
                        $$('#' + this.tilePrefix + image_data.image_id + ' img')[0].observe('mouseover', function() {
                            $(this.locationId).update(place.address);
                            $$('#' + this.tilePrefix + image_data.image_id + ' img')[0].toggleClassName('image-tile-highlight');
                            marker.focus();
                        }.bind(this));
                        $$('#' + this.tilePrefix + image_data.image_id + ' img')[0].observe('mouseout', function() {
                            $(this.locationId).update('<br />');
                            $$('#' + this.tilePrefix + image_data.image_id + ' img')[0].toggleClassName('image-tile-highlight');
                            marker.div_.style.border = '1px solid white';
                            marker.focus();
                        }.bind(this));
                    }

                    return;
                } else if (this.options.viewType == 'Image') {
                    // If Image view and this is the markerOnly point.
                    if (image_data.markerOnly) {
                        if (this.locationId) {
                            $(this.locationId).update(place.address);
                        }
                        if (this.coordId) {
                            $(this.coordId).update(this._point2Deg(marker.getLatLng()));
                        }
                        if (this.relocateId) {
                            $(this.relocateId).update(this._getRelocateLink(image_data.image_id));
                        }

                        if (this.deleteId) {
                            $(this.deleteId).update(this._getDeleteLink(image_data.image_id));
                        }
                    }

                    return;
                } else {
                    // Edit view
                    $(this.locationId).update(place.address);
                    $(this.coordId).update(this._point2Deg(marker.getLatLng()));

                    return;
                }

            } else {
                // Parse less detail, or just move on to the next hit??
            }
        }
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

         return dec2deg(ll.lat(), true) + " " + dec2deg(ll.lng());
    },

    _getRelocateLink: function(iid) {
        if (this.options.hasEdit) {
            var a = new Element('a', {href: this.options.relocateUrl + '?image=' + iid}).update(this.options.relocateText);
            a.observe('click', function(e) { Horde.popup({ url: this.options.relocateUrl, params: 'image=' + iid, width: 750, height: 600 }); e.stop();}.bind(this));
            return a;
        } else {
            return '';
        }
    },

    _getDeleteLink: function(iid) {
        var x = new Element('a', {href: this.options.relocateUrl + '?image=' + iid}).update(this.options.deleteGeotagText);
        x.observe('click', function(e) {this.options.deleteGeotagCallback(); e.stop();}.bindAsEventListener(this));
        return x;
    }

}

/**
 * Define our custom GOverlay to display thumbnails of images on the map.
 * Use an Image object to get the exact dimensions of the image. Need this
 * wrapped in an onload handler to be sure GOverlay() is defined.
 */
document.observe('dom:loaded', function () {
    anselGOverlay = function(latlng, image_data) {
        this.src_ = image_data.icon;
        this.latlng_ = latlng;
        var img = new Image();
        img.src = image_data.icon;
        this.width_ = img.width;
        this.height_ = img.height;
        var z = GOverlay.getZIndex(this.latlng_.lat());
        this.div_ = new Element('div', {style: 'position:absolute;border:1px solid white;width:' + (this.width_ - 2) + 'px; height:' + (this.height_ - 2) + 'px;zIndex:' + z});
        this.img_ = new Element('img', {src: this.src_, style: 'width:' + (this.width_ - 2) + 'px;height:' + (this.height_ - 2) + 'px'});
        this.div_.appendChild(this.img_);
        this.selected_ = false;
        this.link = image_data.link;
    
        // Handlers to hightlight the node for this overlay on mouseover/out
        GEvent.addDomListener(this.div_, 'mouseover', function() {
            this.focus();
        }.bind(this));
        GEvent.addDomListener(this.div_, 'mouseout', function() {
            this.focus();
        }.bind(this));
    
        // Add a click handler to navigate to the image view for this image.
        if (this.link) {
            GEvent.addDomListener(this.div_, 'click', function() {
                    var a = this.link;
                    location.href = a;
                }.bind(this));
            }
        };
    
        anselGOverlay.prototype = new GOverlay();
        anselGOverlay.prototype.initialize =  function(map) {
            map.getPane(G_MAP_MARKER_PANE).appendChild(this.div_);
            this.map_ = map;
        };
    
        //Remove the main DIV from the map pane
        // TODO: We should unregister the event handlers adding in initialize()
        anselGOverlay.prototype.remove = function() {
          this.div_.parentNode.removeChild(this.div_);
        };
    
        // Copy our data to a new GOverlay
        anselGOverlay.prototype.copy = function() {
          return new Ansel_GOverlay(this.latlng_, this.src_);
        };
    
        anselGOverlay.prototype.redraw = function(force) {
            // We only need to redraw if the coordinate system has changed
        if (!force) return;
        var coords = this.map_.fromLatLngToDivPixel(this.latlng_);
        this.div_.style.left = coords.x + "px";
        this.div_.style.top  = coords.y + "px";
    };
    
    anselGOverlay.prototype.focus = function()
    {
        if (this.selected_ == false) {
            this.div_.style.border = '1px solid red';
            this.div_.style.left = (parseInt(this.div_.style.left) - 1) + "px";
            this.div_.style.top = (parseInt(this.div_.style.top) - 1) + "px";
            this.div_.style.zIndex = GOverlay.getZIndex(-90.0);
            this.selected_ = true;
        } else {
            this.div_.style.border = '1px solid white';
            this.div_.style.left = (parseInt(this.div_.style.left) + 1) + "px";
            this.div_.style.top = (parseInt(this.div_.style.top) + 1) + "px";
            this.div_.style.zIndex = GOverlay.getZIndex(this.latlng_.lat());
            this.selected_ = false;
        }
    };
    
    // MarkerManager seems to be incosistent with the methods it calls to get
    // the GLatLng for each overlay. addMarkers() seems to need the deprecated
    // getPoint() while addMarker() uses the newer getLatLng() mehtod.
    anselGOverlay.prototype.getPoint = function() {
        return this.latlng_;
    }
    anselGOverlay.prototype.getLatLng = function() {
        return this.latlng_;
    }
});
