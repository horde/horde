/**
 * You can only have one SlideController on a page.
 *
 * $Horde: ansel/js/src/slideshow.js,v 1.30 2008/06/29 15:31:03 mrubinsk Exp $
 */
var SlideController = {

    photos: null,
    photoId: 0,

    slide: null,

    interval: null,
    intervalSeconds: 5,

    /**
     * CSS border size x 2
     */
    borderSize: 0,

    /**
     * So we can update the links
     */
    baseUrl: null,
    galleryId: 0,
    galleryShare: 0,
    playing: false,
    interval: null,
    tempImage: new Image(),

    /**
     * Initialization.
     */
    initialize: function(photos, start, baseUrl, galleryId, galleryShare) {
        SlideController.photoId = start || 0;
        SlideController.baseUrl = baseUrl;
        SlideController.galleryId = galleryId;
        SlideController.galleryShare = galleryShare;

        Event.observe(window, 'load', function() {
            Event.observe(SlideController.tempImage, 'load', function() {
                SlideController.photo.initSwap();
            });
            Event.observe($('Photo'), 'load', function() {
                SlideController.photo.showPhoto();
            });

            SlideController.photos = photos;
            SlideController.photo = new Slide(SlideController.photoId);
            SlideController.photo.initSwap();
            SlideController.play();

        });
    },

    /**
     */
    play: function() {
        Element.hide('ssPlay');
        Element.show('ssPause');
        // This sets the first interval for the currently displayed image.
        if (SlideController.interval) {
            clearTimeout(SlideController.interval);
        }
        SlideController.interval = setTimeout(SlideController.next, SlideController.intervalSeconds * 1000);
        SlideController.playing = true;
    },

    /**
     */
    pause: function() {
        Element.hide('ssPause');
        Element.show('ssPlay');
        if (SlideController.interval) {
            clearTimeout(SlideController.interval);
        }
        SlideController.playing = false;
    },

    /**
     */
    prev: function() {
        SlideController.photo.prevPhoto();
    },

    /**
     */
    next: function() {
        SlideController.photo.nextPhoto();
    }

}

// -----------------------------------------------------------------------------------
//
// This page coded by Scott Upton
// http://www.uptonic.com | http://www.couloir.org
//
// This work is licensed under a Creative Commons License
// Attribution-ShareAlike 2.0
// http://creativecommons.org/licenses/by-sa/2.0/
//
// Associated APIs copyright their respective owners
//
// -----------------------------------------------------------------------------------
// --- version date: 11/28/05 --------------------------------------------------------
//
// Various changes for properly updating image links, image comments etc...
// added 4/07 by Michael Rubinsky <mrubinsk@horde.org>

/**
 * Additional methods for Element added by SU, Couloir.
 */
Object.extend(Element, {
    getWidth: function(element) {
        element = $(element);
        return element.offsetWidth;
    },
    setWidth: function(element,w) {
        element = $(element);
        element.style.width = w + 'px';
    },
    setSrc: function(element,src) {
        element = $(element);
        element.src = src;
    },
    setHref: function(element,href) {
        element = $(element);
        element.href = href;
    },
    setInnerHTML: function(element,content) {
        element = $(element);
        element.innerHTML = content;
    },
    setOnClick: function(element,action) {
        element = $(element);
        element.onclick = action;
    }
});

var Slide = Class.create();
Slide.prototype = {
    initialize: function(photoId) {
        this.photoId = photoId;
        this.photo = 'Photo';
        this.captionBox = 'CaptionContainer';
        this.caption = 'Caption';
    },
    setNewPhotoParams: function() {
        // Set source of new image.
        Element.setSrc(this.photo, SlideController.photos[SlideController.photoId][0]);

        // Add caption from gallery array.
        Element.setInnerHTML(this.caption, SlideController.photos[SlideController.photoId][2]);

        try {
            document.title = document.title.replace(SlideController.photos[this.photoId][1],
                                                    SlideController.photos[SlideController.photoId][1]);
            if (parent.frames.horde_main) {
                parent.document.title = document.title;
            }
        } catch (e) {}
    },
    updateLinks: function() {

        var params = '?gallery=' + SlideController.galleryId + '&image=' + SlideController.photos[SlideController.photoId][3] + '&page=' + SlideController.photos[SlideController.photoId][4];

        Element.setInnerHTML('PhotoName', SlideController.photos[SlideController.photoId][1]);
        Element.setInnerHTML('breadcrumb_image', SlideController.photos[SlideController.photoId][1]);
        Element.setHref($('breadcrumb_image'), SlideController.baseUrl + '/view.php' + params + '&view=Image');
        Element.setHref($('breadcrumb_gallery'), SlideController.baseUrl + '/view.php' + params + '&view=Gallery');
        if ($('image_properties_link')) {
            Element.setHref('image_properties_link', SlideController.baseUrl + '/image.php' + params + '&actionID=modify&share=' + SlideController.galleryShare);
            Element.setOnClick('image_properties_link', function(){SlideController.pause();popup(this.href);return false;});
        }
        if ($('image_edit_link')) {
            Element.setHref('image_edit_link', SlideController.baseUrl + '/image.php' + params + '&actionID=editimage');
        }
        if ($('image_ecard_link')) {
            Element.setHref('image_ecard_link', SlideController.baseUrl + '/img/ecard.php?image=' + SlideController.photos[SlideController.photoId][3] + '&gallery=' + SlideController.galleryId);
            Element.setOnClick('image_ecard_link', function(){SlideController.pause();popup(this.href);return false;});
        }
        if ($('image_delete_link')) {
            //TODO : Guess we should have PHP save the localized text for this...
            var deleteAction = function() {return window.confirm("Do you want to permanently delete " +  SlideController.photos[SlideController.photoId][1])};
            Element.setHref($("image_delete_link"), SlideController.baseUrl + '/image.php' + params + '&actionID=delete');
            Element.setOnClick('image_delete_link', deleteAction);
        }
        Element.setHref('image_download_link', SlideController.baseUrl + '/img/download.php?image=' + SlideController.photos[SlideController.photoId][3]);
        Element.setOnClick('image_download_link', function(){SlideController.pause();});
    },

    showPhoto: function() {
        new Effect.Appear(this.photo, { duration: 1.0, queue: 'end', afterFinish: (function() { Element.show(this.captionBox); this.updateLinks();}).bind(this) });

        if (SlideController.playing) {
            if (SlideController.interval) {
                clearTimeout(SlideController.interval);
            }
            SlideController.interval = setTimeout(SlideController.next, SlideController.intervalSeconds * 1000);
        }
    },
    nextPhoto: function() {
        // Figure out which photo is next.
        (SlideController.photoId == (SlideController.photos.length - 1)) ? SlideController.photoId = 0 : ++SlideController.photoId;
        // Make sure the photo is loaded locally before we fade the current image.
        SlideController.tempImage.src = SlideController.photos[SlideController.photoId][0];

    },
    prevPhoto: function() {
        // Figure out which photo is previous.
        (SlideController.photoId == 0) ? SlideController.photoId = SlideController.photos.length - 1 : --SlideController.photoId;
        SlideController.tempImage.src = SlideController.photos[SlideController.photoId][0];
    },
    initSwap: function() {
        // Begin by hiding main elements.
        new Effect.Fade(this.captionBox, {duration: 0.5 });
        new Effect.Fade(this.photo, { duration: 1.0, afterFinish: (function() { SlideController.photo.setNewPhotoParams();})});

        // Update the current photo id.
        this.photoId = SlideController.photoId;
    }
}
 // Arrow keys for navigation
 document.observe('keydown', arrowHandler);
function arrowHandler(e)
{
    if (e.altKey || e.shiftKey || e.ctrlKey) {
        return;
    }

    switch (e.keyCode || e.charCode) {
    case Event.KEY_LEFT:
        SlideController.prev();
        break;

    case Event.KEY_RIGHT:
        SlideController.next();
        break;
    }
}
