/**
 * ansel.js - Base application logic.
 *
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */

/* Ansel object. */
AnselCore = {
    // Vars used and defaulting to null/false:

    view: '',
    viewLoading: [],
    redBoxLoading: false,
    knl: {},
    wrongFormat: $H(),
    mapMarker: null,
    map: null,
    mapInitialized: false,
    freeBusy: $H(),
    effectDur: 0.4,
    macos: navigator.appVersion.indexOf('Mac') != -1,

    /**
     * The location that was open before the current location.
     *
     * @var string
     */
    lastLocation: '',

    /**
     * The currently open location.
     *
     * @var string
     */
    openLocation: '',

    /**
     * The current (main) location.
     *
     * This is different from openLocation as it isn't updated for any
     * locations that are opened in a popup view, e.g. events.
     *
     * @var string
     */
    currentLocation: '',

    anselBody: $('anselBody'),

    onException: function(parentfunc, r, e)
    {
        /* Make sure loading images are closed. */
        this.loading--;
        if (!this.loading) {
            $('anselLoading').hide();
        }
        this.closeRedBox();
        HordeCore.notify(HordeCore.text.ajax_error, 'horde.error');
        parentfunc(r, e);
    },

    setTitle: function(title)
    {
        document.title = Ansel.conf.name + ' :: ' + title;
        return title;
    },

    // url = (string) URL to redirect to
    // hash = (boolean) If true, url is treated as hash information to alter
    //        on the current page
    redirect: function(url, hash)
    {
        if (hash) {
            window.location.hash = escape(url);
            window.location.reload();
        } else {
            HordeCore.redirect(url);
        }
    },

    go: function(fullloc, data)
    {
        if (!this.initialized) {
            this.go.bind(this, fullloc, data).defer();
            return;
        }

        if (this.viewLoading.size()) {
            this.viewLoading.push([ fullloc, data ]);
            return;
        }

        var locParts = fullloc.split(':');
        var loc = locParts.shift();

        if (this.openLocation == fullloc) {
            return;
        }

        this.viewLoading.push([ fullloc, data ]);

        // if (loc != 'search') {
        //     HordeTopbar.searchGhost.reset();
        // }

        switch (loc) {
        case 'browse':
            this.closeView(loc);
            var locCap = loc.capitalize();
            $('anselNav' + locCap).up().addClassName('horde-active');

            switch (loc) {
            case 'browse':
                this.addHistory(fullloc);
                this.view = loc;
                this.updateView(loc);
                $('anselView' + locCap).appear({
                        duration: this.effectDur,
                        queue: 'end',
                        afterFinish: function() {
                            this.loadNextView();
                        }.bind(this)
                });
                $('anselLoading' + loc).insert($('anselLoading').remove());
                break;

            default:
                if (!$('kronolithView' + locCap)) {
                    break;
                }
                this.addHistory(fullloc);
                this.view = loc;
                $('kronolithView' + locCap).appear({
                    duration: this.effectDur,
                    queue: 'end',
                    afterFinish: function() {
                        this.loadNextView();
                    }.bind(this) });
                break;
            }

            break;
        }
    },

    /**
     * Removes the last loaded view from the stack and loads the last added
     * view, if the stack is still not empty.
     *
     * We want to load views from a LIFO queue, because the queue is only
     * building up if the user switches to another view while the current view
     * still loads. In that case we can go directly to the most recently
     * clicked view and drop the remaining queue.
     */
    loadNextView: function()
    {
        var current = this.viewLoading.shift();
        if (this.viewLoading.size()) {
            var next = this.viewLoading.pop();
            this.viewLoading = [];
            if (current[0] != next[0] || current[1] || next[1]) {
                this.go(next[0], next[1]);
            }
        }
    },

    /**
     * Rebuilds one of the views
     *
     * @param string view  The view that's rebuilt.
     * @param mixed data   Any additional data that might be required.
     */
    updateView: function(view, data)
    {
        switch (view) {
        case 'browse':
            break;
        }
    },

    /**
     * Sets the browser title of the calendar views.
     *
     * @param string view  The view that's displayed.
     * @param mixed data   Any additional data that might be required.
     */
    setViewTitle: function(view, data)
    {
        // switch (view) {
        // case 'day':
        //     return this.setTitle(date.toString('D'));

        // case 'week':
        // case 'workweek':
        //     var dates = this.viewDates(date, view);
        //     return this.setTitle(dates[0].toString(Kronolith.conf.date_format) + ' - ' + dates[1].toString(Kronolith.conf.date_format));

        // case 'month':
        //     return this.setTitle(date.toString('MMMM yyyy'));

        // case 'year':
        //     return this.setTitle(date.toString('yyyy'));

        // case 'agenda':
        //     var dates = this.viewDates(date, view);
        //     return this.setTitle(dates[0].toString(Kronolith.conf.date_format) + ' - ' + dates[1].toString(Kronolith.conf.date_format));

        // case 'search':
        //     return this.setTitle(Kronolith.text.searching.interpolate({ term: data })).escapeHTML();
        // }
    },

    /**
     * Closes the currently active view.
     */
    closeView: function(loc)
    {
        $w('Browse').each(function(a) {
            a = $('anselNav' + a);
            if (a) {
                a.up().removeClassName('horde-active');
            }
        });
        if (this.view && this.view != loc) {
            $('anselView' + this.view.capitalize()).fade({
                duration: this.effectDur,
                queue: 'end'
            });
            this.view = null;
        }
    },

    equalRowHeights: function(tbody)
    {
        var children = tbody.childElements();
        children.invoke('setStyle', { height: (100 / (children.size() - 1)) + '%' });
    },

    /**
     * Calculates some dimensions for the day and week view.
     *
     * @param string storage  Property name where the dimensions are stored.
     * @param string view     DOM node ID of the view.
     */
    // calculateRowSizes: function(storage, view)
    // {
    //     if (!Object.isUndefined(this[storage])) {
    //         return;
    //     }

    //     var td = $(view).down('.kronolithViewBody tr td').next('td'),
    //         layout = td.getLayout(),
    //         spacing = td.up('table').getStyle('borderSpacing');

    //     // FIXME: spacing is hardcoded for IE 7 because it doesn't know about
    //     // border-spacing, but still uses it. WTF?
    //     spacing = spacing ? parseInt($w(spacing)[1], 10) : 2;
    //     this[storage] = {};
    //     this[storage].height = layout.get('margin-box-height') + spacing;
    //     this[storage].spacing = this[storage].height - layout.get('padding-box-height') - layout.get('border-bottom');
    // },

    /**
     * Inserts a gallery entry in the sidebar menu.
     *
     * @param string id    The gallery id.
     * @param object gal   The gallery object.
     * @param Element div  Container DIV where to add the entry (optional).
     */
    insertGalleryInList: function(id, gal, div)
    {
    },

    /**
     * Add the share icon after the gallery name in the gallery list.
     *
     * @param object  gal      A gallery object.
     * @param Element element  The gallery element in the list.
     */
    addShareIcon: function(gal, element)
    {
    },

    /**
     * Rebuilds the list of galleries.
     */
    updateGalleryList: function()
    {
    },

    /**
     * Loads a certain gallery.
     *
     * @param string gallery  The gallery id.
     */
    loadGallery: function(gallery)
    {
    },

    /**
     */
    loadImages: function(firstImage, lastImage, view, gallery)
    {

    },

    /**
     * Callback method for loading images.
     *
     * @param object r             The ajax response object.
     * @param boolean createCache  Whether to create a cache list entry for the
     *                             response, if none exists yet. Useful for
     *                             (not) adding individual images to the cache
     *                             if it doesn't match any cached views.
     */
    loadImagesCallback: function(r, createCache)
    {

    },

    /**
     * Reads events from the cache and inserts them into the view.
     *
     * If inserting events into day and week views, the calendar parameter is
     * ignored, and events from all visible calendars are inserted instead.
     * This is necessary because the complete view has to be re-rendered if
     * events are not in chronological order.
     * The year view is specially handled too because there are no individual
     * events, only a summary of all events per day.
     *
     * @param Array imageNumbers Start and end image numbers.
     * @param string view      The view to update.
     * @param string gallery  The gallery to update.
     */
    insertImages: function(imageNumbers, view, gallery)
    {

    },

    /**
     * Creates the DOM node for an event bubble and inserts it into the view.
     *
     * @param object image    A Hash member with the image to insert.
     * @param string view     The view to update.
     */
    insertImage: function(image, view)
    {

    },


    /**
     * Finally removes events from the DOM and the cache.
     *
     * @param string gallery   A gallery id.
     * @param string image     An image id. If empty, all images from the
     *                         gallery are deleted.
     */
    removeImage: function(gallery, image)
    {
        //this.deleteCache(gallery, image);
    },

    /**
     * Opens the form for editing a gallery.
     *
     * @param string gallery  The gallery id.
     */
    editGallery: function(gallery)
    {

    },

    /**
     * Callback for editing a gallery. Fills the edit form with the correct
     * values.
     *
     * @param string gallery  Gallery id.
     */
    editGalleryCallback: function(gallery)
    {
    },

    /**
     * Submits the gallery form to save the gallery data.
     *
     * @param Element form  The form node.
     *
     * @return boolean  Whether the save request was successfully sent.
     */
    saveGallery: function(form)
    {
    },

    /**
     * Callback method after saving a gallery.
     *
     * @param Element form  The form node.
     * @param object data   The serialized form data.
     * @param object r      The ajax response object.
     */
    saveGalleryCallback: function(form, data, r)
    {

    },

    /**
     * Deletes a gallery and all of it's images.
     *
     * @param string id      The gallery id.
     */
    deleteGallery: function(gallery)
    {
    },

    /**
     * Adds a new location to the history and displays it in the URL hash.
     *
     * This is not really a history, because only the current and the last
     * location are stored.
     *
     * @param string loc    The location to save.
     * @param boolean save  Whether to actually save the location. This should
     *                      be false for any location that are displayed on top
     *                      of another location, i.e. in a popup view.
     */
    addHistory: function(loc, save)
    {
        location.hash = encodeURIComponent(loc);
        this.lastLocation = this.currentLocation;
        if (Object.isUndefined(save) || save) {
            this.currentLocation = loc;
        }
        this.openLocation = loc;
    },

    /**
     * Loads an external page.
     *
     * @param string loc  The URL of the page to load.
     */
    loadPage: function(loc)
    {
        window.location.assign(loc);
    },

    searchSubmit: function(e)
    {
        this.go('search:' + this.search + ':' + $F('horde-search-input'));
    },

    searchReset: function(e)
    {
        HordeTopbar.searchGhost.reset();
    },

    /**
     * Event handler for HordeCore:showNotifications events.
     */
    showNotification: function(e)
    {
        if (!e.memo.flags ||
            !e.memo.flags.alarm ||
            !e.memo.flags.growl ||
            !e.memo.flags.alarm.params.notify.ajax) {
            return;
        }

        var growl = e.memo.flags.growl, link = growl.down('A');

        if (link) {
            link.observe('click', function(ee) {
                ee.stop();
                HordeCore.Growler.ungrowl(growl);
                this.go(e.memo.flags.alarm.params.notify.ajax);
            }.bind(this));
        }
    },

    /* Keydown event handler */
    keydownHandler: function(e)
    {
        if (e.stopped) {
            return;
        }

        var kc = e.keyCode || e.charCode,
            form = e.findElement('FORM'), trigger = e.findElement();

        switch (trigger.id) {
        }
    },

    keyupHandler: function(e)
    {
    },

    clickHandler: function(e, dblclick)
    {
        if (e.isRightClick() || typeof e.element != 'function') {
            return;
        }

        var elt = e.element(),
            orig = e.element(),
            id, tmp, calendar;

        // while (Object.isElement(elt)) {
        //     id = elt.readAttribute('id');

        //     switch (id) {
        //     //return
        //     }

        //     // Caution, this only works if the element has definitely only a
        //     // single CSS class.
        //     switch (elt.className) {
        //     //return
        //     }

        //     if (elt.hasClassName()) {
        //     //return
        //     } else if () {
        //     //return
        //     }

        //     elt = elt.up();
        // }
        // Workaround Firebug bug.
        Prototype.emptyFunction();
    },

    /**
     * Closes a RedBox overlay, after saving its content to the body.
     */
    closeRedBox: function()
    {
        if (!RedBox.getWindow()) {
            return;
        }
        var content = RedBox.getWindowContents();
        if (content) {
            document.body.insert(content.hide());
        }
        RedBox.close();
    },

    // By default, no context onShow action
    contextOnShow: Prototype.emptyFunction,

    // By default, no context onClick action
    contextOnClick: Prototype.emptyFunction,

    // Map
    initializeMap: function(ignoreLL)
    {
        // if (this.mapInitialized) {
        //     return;
        // }
        // var layers = [];
        // if (Kronolith.conf.maps.providers) {
        //     Kronolith.conf.maps.providers.each(function(l) {
        //         var p = new HordeMap[l]();
        //         $H(p.getLayers()).values().each(function(e) {layers.push(e);});
        //     });
        // }

        // this.map = new HordeMap.Map[Kronolith.conf.maps.driver]({
        //     elt: 'kronolithEventMap',
        //     delayed: true,
        //     layers: layers,
        //     markerDragEnd: this.onMarkerDragEnd.bind(this),
        //     mapClick: this.afterClickMap.bind(this)
        // });

        // if ($('kronolithEventLocationLat').value && !ignoreLL) {
        //     var ll = { lat:$('kronolithEventLocationLat').value, lon: $('kronolithEventLocationLon').value };
        //     // Note that we need to cast the value of zoom to an integer here,
        //     // otherwise the map display breaks.
        //     this.placeMapMarker(ll, true, $('kronolithEventMapZoom').value - 0);
        // }
        // //@TODO: check for Location field - and if present, but no lat/lon value, attempt to
        // // geocode it.
        // this.map.display();
        // this.mapInitialized = true;
    },

    resetMap: function()
    {
        // this.mapInitialized = false;
        // $('kronolithEventLocationLat').value = null;
        // $('kronolithEventLocationLon').value = null;
        // $('kronolithEventMapZoom').value = null;
        // if (this.mapMarker) {
        //     this.map.removeMarker(this.mapMarker, {});
        //     this.mapMarker = null;
        // }
        // if (this.map) {
        //     this.map.destroy();
        //     this.map = null;
        // }
    },

    /**
     * Callback for handling marker drag end.
     *
     * @param object r  An object that implenents a getLonLat() method to obtain
     *                  the new location of the marker.
     */
    onMarkerDragEnd: function(r)
    {
        // var ll = r.getLonLat();
        // $('kronolithEventLocationLon').value = ll.lon;
        // $('kronolithEventLocationLat').value = ll.lat;
        // var gc = new HordeMap.Geocoder[Kronolith.conf.maps.geocoder](this.map.map, 'kronolithEventMap');
        // gc.reverseGeocode(ll, this.onReverseGeocode.bind(this), this.onGeocodeError.bind(this) );
    },

    /**
     * Callback for handling a reverse geocode request.
     *
     * @param array r  An array of objects containing the results. Each object in
     *                 the array is {lat:, lon:, address}
     */
    onReverseGeocode: function(r)
    {
        // if (!r.length) {
        //     return;
        // }
        // $('kronolithEventLocation').value = r[0].address;
    },

    onGeocodeError: function(r)
    {
        // $('kronolithEventGeo_loading_img').toggle();
        // HordeCore.notify(Kronolith.text.geocode_error + ' ' + r, 'horde.error');
    },

    /**
     * Callback for geocoding calls.
     */
    onGeocode: function(r)
    {
        // $('kronolithEventGeo_loading_img').toggle();
        // r = r.shift();
        // if (r.precision) {
        //     zoom = r.precision * 2;
        // } else {
        //     zoom = null;
        // }
        // this.ensureMap(true);
        // this.placeMapMarker({ lat: r.lat, lon: r.lon }, true, zoom);
    },

    geocode: function(a)
    {
        // if (!a) {
        //     return;
        // }
        // $('kronolithEventGeo_loading_img').toggle();
        // var gc = new HordeMap.Geocoder[Kronolith.conf.maps.geocoder](this.map.map, 'kronolithEventMap');
        // gc.geocode(a, this.onGeocode.bind(this), this.onGeocodeError);
    },

    /**
     * Place the event marker on the map, at point ll, ensuring it exists.
     * Optionally center the map on the marker and zoom. Zoom only honored if
     * center is set, and if center is set, but zoom is null, we zoomToFit().
     *
     */
    placeMapMarker: function(ll, center, zoom)
    {
        // if (!this.mapMarker) {
        //     this.mapMarker = this.map.addMarker(
        //             ll,
        //             { draggable: true },
        //             {
        //                 context: this,
        //                 dragend: this.onMarkerDragEnd
        //             });
        // } else {
        //     this.map.moveMarker(this.mapMarker, ll);
        // }

        // if (center) {
        //     this.map.setCenter(ll, zoom);
        //     if (!zoom) {
        //         this.map.zoomToFit();
        //     }
        // }
        // $('kronolithEventLocationLon').value = ll.lon;
        // $('kronolithEventLocationLat').value = ll.lat;
    },

    /**
     * Remove the event marker from the map. Called after clearing the location
     * field.
     */
    removeMapMarker: function()
    {
        // if (this.mapMarker) {
        //     this.map.removeMarker(this.mapMarker, {});
        //     $('kronolithEventLocationLon').value = null;
        //     $('kronolithEventLocationLat').value = null;
        // }

        // this.mapMarker = false;
    },

    /**
     * Ensures the map tab is visible and sets UI elements accordingly.
     */
    ensureMap: function(ignoreLL)
    {
        // if (!this.mapInitialized) {
        //     this.initializeMap(ignoreLL);
        // }
        // var dialog = $('kronolithEventForm');
        // dialog.select('.kronolithTabsOption').invoke('hide');
        // dialog.select('.tabset li').invoke('removeClassName', 'horde-active');
        // $('kronolithEventTabMap').show();
        // $('kronolithEventLinkMap').up().addClassName('horde-active');
    },

    /**
     * Callback that gets called after a new marker has been placed on the map
     * due to a single click on the map.
     *
     * @return object o  { lonlat: }
     */
    afterClickMap: function(o)
    {
        // this.placeMapMarker(o.lonlat, false);
        // var gc = new HordeMap.Geocoder[Kronolith.conf.maps.geocoder](this.map.map, 'kronolithEventMap');
        // gc.reverseGeocode(o.lonlat, this.onReverseGeocode.bind(this), this.onGeocodeError.bind(this) );
    },

    /* Onload function. */
    onDomLoad: function()
    {
        /* Initialize the starting page. */
        var tmp = location.hash;
        if (!tmp.empty() && tmp.startsWith('#')) {
            tmp = (tmp.length == 1) ? '' : tmp.substring(1);
        }
        if (tmp.empty()) {
            this.updateView(this.date, Ansel.conf.login_view);
           // $('anselView' + Ansel.conf.login_view.capitalize()).show();
        }
        //HordeCore.doAction('listGalleries', {}, { callback: this.initialize.bind(this, tmp) })

        RedBox.onDisplay = function() {
            this.redBoxLoading = false;
        }.bind(this);
        RedBox.duration = this.effectDur;

        document.observe('keydown', AnselCore.keydownHandler.bindAsEventListener(AnselCore));
        document.observe('keyup', AnselCore.keyupHandler.bindAsEventListener(AnselCore));
        document.observe('click', AnselCore.clickHandler.bindAsEventListener(AnselCore));
        document.observe('dblclick', AnselCore.clickHandler.bindAsEventListener(AnselCore, true));
    },

    initialize: function(location, r)
    {
        Ansel.conf.galleries = r.galleries;
        this.updateGalleryList();
        //$('anselLoadingGalleries').hide();
        this.initialized = true;

        /* Initialize the starting page. */
        if (!location.empty()) {
            this.go(decodeURIComponent(location));
        } else {
            this.go(Ansel.conf.login_view);
        }

        /* Start polling. */
        new PeriodicalExecuter(function()
            {
                HordeCore.doAction('poll');
            },
            60
        );
        console.log("foo");
    }

};

/* Initialize global event handlers. */
document.observe('dom:loaded', AnselCore.onDomLoad.bind(AnselCore));
// document.observe('FormGhost:reset', AnselCore.searchReset.bindAsEventListener(AnselCore));
// document.observe('FormGhost:submit', AnselCore.searchSubmit.bindAsEventListener(AnselCore));
document.observe('HordeCore:showNotifications', AnselCore.showNotification.bindAsEventListener(AnselCore));
if (Prototype.Browser.IE) {
    $('anselBody').observe('selectstart', Event.stop);
}

/* Extend AJAX exception handling. */
HordeCore.onException = HordeCore.onException.wrap(AnselCore.onException.bind(AnselCore));
