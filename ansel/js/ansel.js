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
AnselCore =
{
    // The current view name.
    view: '',

    // Current subview name.
    subview: '',

    // Stack of loading views.
    viewLoading: [],

    // KeyNavList - for autocompleting tags.
    knl: {},

    effectDur: 0.4,
    inScrollHandler: false,
    perPage: 10,

    // The imageview object.
    imageLayout: null,

    // The gallery layout object.
    galleryLayout: null,

    // Layout object for image list.
    imagesLayout: null,

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

    onException: function(parentfunc, r, e)
    {
        /* Make sure loading images are closed. */
        this.loading--;
        if (!this.loading) {
            $('anselLoading').hide();
        }
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

    // Navigate to a view or subview
    // fullloc - the main view (me, groups, etc...)
    // data    - An object containing additional data used:
    //      - subview
    //      - gid
    //
    //
    go: function(fullloc, data)
    {
        if (!this.initialized) {
            this.go.bind(this, fullloc, data).defer();
            return;
        }

        var locParts = fullloc.split(':');
        var loc = locParts.shift();
        var subview = locParts.shift();

        if (!data) {
            data = locParts.shift();
        }
        if (this.viewLoading.size()) {
            this.viewLoading.push([ fullloc, subview ]);
            return;
        }

        // Same location, and subview - exit.
        if (this.openLocation && this.openLocation == fullloc) {
            if (this.subview && !data && subview == this.subview) {
                return;
            } else {
                this.closeView(loc, subview);
            }
        } else if (this.openLocation) {
            this.closeView(loc, subview);
        }
        this.viewLoading.push([ fullloc, data ]);

        var locCap = loc.capitalize();
        switch (loc) {
        case 'me':
        case 'all':
        case 'subscribed':
        case 'upload':
            if (loc != 'upload' && subview != 'image') {
                $('anselNav' + locCap).addClassName('horde-subnavi-active');
                $('anselMenu' + subview.capitalize()).up().addClassName('horde-active');
            }
            $('anselHeader').show();
            switch (loc) {
            case 'me':
            case 'all':
                this.view = loc;
                this.subview = subview;
                this.addHistory(fullloc);
                $('anselView' + subview.capitalize()).appear({
                        duration: this.effectDur,
                        queue: 'end',
                        afterFinish: function() {
                            this.updateView(loc, subview, data);
                            this.loadNextView();
                        }.bind(this)
                });
                //$('anselLoading' + loc).insert($('anselLoading').remove());
                break;
            case 'upload':
                $('anseluploader').update();
                $('anselHeader').hide();
            default:
                if (!$('anselView' + locCap)) {
                    break;
                }
                this.addHistory(fullloc);
                this.view = loc;
                this.subview = subview;
                $('anselView' + locCap).appear({
                    duration: this.effectDur,
                    queue: 'end',
                    afterFinish: function() {
                        this.updateView(loc, subview, data);
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
    updateView: function(view, subview, data)
    {
        switch (view) {
        case 'me':
        case 'all':
            switch (subview) {
            case 'images':
                $('anselViewImages').observe('AnselLayout:scroll', this.onImageScroll.bindAsEventListener(this));
                $('anselViewGalleries').stopObserving('AnselLayout:scroll', this.onGalleryScroll.bindAsEventListener(this));
                this.addHistory(view + ':' + subview);
                HordeCore.doAction(
                    'listImages',
                    { view: view, start: 0, count: this.perPage },
                    { callback: this.listImagesCallback.bind(this) }
                );
                break;
            case 'galleries':
                $('anselViewImages').stopObserving('AnselLayout:scroll', this.onImageScroll.bindAsEventListener(this));
                $('anselViewGalleries').observe('AnselLayout:scroll', this.onGalleryScroll.bindAsEventListener(this));
                if (data) {
                    this.addHistory(view + ':' + subview + ':' + data);
                    this.loadGallery(data);
                } else {
                    this.addHistory(view + ':' + subview);
                    HordeCore.doAction('listGalleries', {}, { callback: this.listGalleriesCallback.bind(this) });
                }
                break;
            case 'image':
                $('anselViewImages').stopObserving('AnselLayout:scroll', this.onImageScroll.bindAsEventListener(this));
                $('anselViewGalleries').stopObserving('AnselLayout:scroll', this.onGalleryScroll.bindAsEventListener(this));
                if (data.id) {
                    this.loadImageView(data);
                } else {
                    HordeCore.doAction('getImage', { id: data }, { callback: this.loadImageView.bind(this) });
                }
                break;
            }
            break;

        case 'upload':
            this.addHistory(view);
            HordeCore.doAction(
                'selectGalleries',
                {},
                { callback: this.uploaderListGalleriesCallback.bind(this) }
            );
        }
    } ,

    // Callback responsible for displaying uploader
    uploaderListGalleriesCallback: function(r)
    {
        $('ansel-gallery-select').update(r);
        var uploader = new Horde_Uploader({
            drop_target: 'filelist',
            filelist_class: 'ansel-uploader-filelist',
            container: 'anseluploader',
            text: Ansel.text.uploader,
            swf_path: Ansel.conf.jsuri + '/plupload/plupload.flash.swf',
            xap_path: Ansel.conf.jsuri + '/plupload/plupload.silverlight.xap'
        },
        {
            statechanged: function(up) {
                if (up.state == plupload.STARTED) {
                    up.settings.url = up.settings.page_url + '/img/upload.php?gallery=' + $('ansel-gallery-select').value;
                }
            },
            'uploadcomplete': function(up, files) {
                $('uploadimages').hide();
                this.setReturnCallback(function(e) { AnselCore.go('me:galleries', $('ansel-gallery-select').value); e.stop(); });
            }
        });
        uploader.init();
    },

    onImageScroll: function(e)
    {
        if (!this.inScrollHandler) {
            this.inScrollHandler = true;
            HordeCore.doAction(
                'listImages',
                { view: this.view, start: e.memo.image, count: this.perPage },
                { callback: this.listImagesCallback.bind(this) }
            );
        }
    },

    onGalleryScroll: function(e)
    {
    },

    onGalleryClick: function(e)
    {
        this.go(this.view + ':' + this.subview, e.memo.gid);
    },

    listImagesCallback: function(r)
    {
        this.imagesLayout.addImages(r);
        this.inScrollHandler = false;
    },

    listGalleriesCallback: function(r)
    {
        this.galleryLayout.galleries = $H(r).values();
        this.galleryLayout.resize();
    },

    /**
     * Sets the browser title of the calendar views.
     *
     * @param string view  The view that's displayed.
     * @param mixed data   Any additional data that might be required.
     */
    setViewTitle: function(view, data)
    {
        switch (view) {
        case 'me':
            return this.setTitle('test');
        }
    },

    /**
     * Closes the currently active view.
     *
     * loc - the *current* location
     * subview - the *currently selected* subview.
     */
    closeView: function(loc, subview)
    {
        $w('Me All Subscribed').each(function(a) {
            a = $('anselNav' + a);
            console.log(a);
            if (a) {
                a.removeClassName('horde-subnavi-active');
            }
        });
        $w('Images Galleries Map Date Tags').each(function(a) {
            a = $('anselMenu' + a);
            if (a) {
                a.up().removeClassName('horde-active');
            }
        });
        if (loc == 'upload') {
            this.subview == 'upload';
        }
        if (this.subview) {
            $('anselView' + this.subview.capitalize()).fade({
                duration: this.effectDur,
                queue: 'end',
                afterFinish: function() {
                    if (subview == 'galleries') {
                        this.galleryLayout.reset();
                    } else if (subview == 'images') {
                        this.imagesLayout.reset();
                    }
                }.bind(this)
            });
            if (this.subview == 'image') {
                this.imageLayout.reset();
            }
        }
    },

    // Show an image.
    loadImageView: function(photo)
    {
        this.imageLayout.showImage(photo);
    },

    // Close image view.
    closeImageView: function()
    {
        if (this.lastLocation) {
            this.go(this.lastLocation);
        } else {
            this.go('me:galleries');
        }
    },

    /**
     * Loads a certain gallery.
     *
     * @param string gallery  The gallery id.
     */
    loadGallery: function(gallery)
    {
        HordeCore.doAction('getGallery',
            { id: gallery },
            { callback: this.getGalleryCallback.bind(this) }
        );
    },

    getGalleryCallback: function(r)
    {
        this.galleryLayout.reset();
        this.galleryLayout.addImages(r.imgs);
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

    clickHandler: function(e, dblclick)
    {
        if (e.isRightClick() || typeof e.element != 'function') {
            return;
        }

        var elt = e.element(), id;

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'anselMenuImages':
                this.go(this.view + ':images');
                return;
            case 'anselMenuGalleries':
                this.go(this.view + ':galleries');
                return;
            case 'anselUpload':
                this.go('upload:upload');
                return;

            case 'anselNavMe':
                this.go('me:images');
                return;

            case 'anselNavAll':
                this.go('all:images');
                return;

            }

            // Caution, this only works if the element has definitely only a
            // single CSS class.
            switch (elt.className) {
            case 'ansel-tile-target':
                this.go('me:image:' + elt.retrieve('photo').id, elt.retrieve('photo'));
                break;
            // case 'ansel-imageview-close':
            //     this.go(this.lastLocation);
            }

            elt = elt.up();
        }
        // Workaround Firebug bug.
        Prototype.emptyFunction();
    },

    navigateNext: function(e)
    {
        var idx = this.imageLayout.currentImage.idx;
        if (this.galleryLayout.images.length > idx + 1) {
            this.loadImageView(this.galleryLayout.images[idx + 1]);
        } else {
            this.loadImageView(null);
        }
        e.stop();
    },

    navigatePrevious: function(e)
    {
        var idx = this.imageLayout.currentImage.idx;
        if (!idx == 0) {
            this.loadImageView(this.galleryLayout.images[idx - 1]);
        } else {
            this.loadImageView(null);
        }
        e.stop();
    },

    /* Onload function. */
    onDomLoad: function()
    {
        /* Initialize the starting page. */
        var tmp = location.hash;
        if (!tmp.empty() && tmp.startsWith('#')) {
            tmp = (tmp.length == 1) ? '' : tmp.substring(1);
        }

        // Event handlers
        document.observe('click', AnselCore.clickHandler.bindAsEventListener(AnselCore));
        document.observe('dblclick', AnselCore.clickHandler.bindAsEventListener(AnselCore, true));
        $('anselViewGalleries').observe('AnselLayout:galleryClick', this.onGalleryClick.bindAsEventListener(this));
        $('anselViewImage').observe('AnselImageView:close', function() { this.closeImageView(); }.bind(this));
        $('anselViewImage').observe('AnselImageView:next', this.navigateNext.bindAsEventListener(this));
        $('anselViewImage').observe('AnselImageView:previous', this.navigatePrevious.bindAsEventListener(this));
        this.initialize(tmp);
    },

    initialize: function(location)
    {
        this.imagesLayout = new AnselLayout({
            container: 'anselViewImages',
            perPage: this.perPage
        });

        this.galleryLayout = new AnselLayout({
            container: 'anselViewGalleries',
            perPage: this.perPage
        });

        this.imageLayout = new AnselImageView({
            container: 'anselViewImage'
        });

        this.initialized = true;

        /* Initialize the starting page. */
        if (!location.empty()) {
            this.go(decodeURIComponent(location));
        } else {
            this.go('me:galleries');
        }

        /* Start polling. */
        new PeriodicalExecuter(function()
            {
                HordeCore.doAction('poll');
            },
            60
        );
    }

};

/* Initialize global event handlers. */
document.observe('dom:loaded', AnselCore.onDomLoad.bind(AnselCore));
document.observe('HordeCore:showNotifications', AnselCore.showNotification.bindAsEventListener(AnselCore));
if (Prototype.Browser.IE) {
    $('anselBody').observe('selectstart', Event.stop);
}

/* Extend AJAX exception handling. */
HordeCore.onException = HordeCore.onException.wrap(AnselCore.onException.bind(AnselCore));
