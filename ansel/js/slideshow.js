/**
 * You can only have one SlideController on a page.
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
     * 
     */
    play: function() {
        $('ssPlay').hide();
        $('ssPause').show();
        // This sets the first interval for the currently displayed image.
        if (SlideController.interval) {
            clearTimeout(SlideController.interval);
        }
        SlideController.interval = setTimeout(SlideController.next, SlideController.intervalSeconds * 1000);
        SlideController.playing = true;
    },

    /**
     * Leaving this in here, but currently we just redirect back to the Image view
     */
    pause: function() {
        $('ssPause').hide();
        $('ssPlay').show();
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
// Various changes for properly updating image links, image comments, get rid
// of redundant functions that prototype can take care of etc...
// added 4/07 by Michael Rubinsky <mrubinsk@horde.org>
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
        $(this.photo).src = SlideController.photos[SlideController.photoId][0];

        // Add caption from gallery array.
        $(this.caption).update(SlideController.photos[SlideController.photoId][2]);

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
        $('PhotoName').update(SlideController.photos[SlideController.photoId][1]);
        if ($('image_properties_link')) {
            $('image_properties_link').href = SlideController.baseUrl + '/image.php' + params + '&actionID=modify&share=' + SlideController.galleryShare;
            $('image_properties_link').observe('click', function(){SlideController.pause();Horde.popup({ url: this.href });return false;});
        }
        if ($('image_edit_link')) {
            $('image_edit_link').href = SlideController.baseUrl + '/image.php' + params + '&actionID=editimage';
        }
        if ($('image_ecard_link')) {
          $('image_ecard_link') = SlideController.baseUrl + '/img/ecard.php?image=' + SlideController.photos[SlideController.photoId][3] + '&gallery=' + SlideController.galleryId;
          $('image_ecard_link').observe('click', function(){SlideController.pause();Horde.popup({ url: this.href });return false;});
        }
        if ($('image_delete_link')) {
            //TODO : Guess we should have PHP save the localized text for this...
            var deleteAction = function() { SlideController.pause(); if (!window.confirm("Do you want to permanently delete " +  SlideController.photos[SlideController.photoId][1])) { alert("blah"); return false; } return true;};
            $('image_delete_link').href = SlideController.baseUrl + '/image.php' + params + '&actionID=delete';
            $('image_delete_link').observe('click', function(e) { return deleteAction(); e.stop(); });
        }
        $('image_download_link').href = SlideController.baseUrl + '/img/download.php?image=' + SlideController.photos[SlideController.photoId][3];
        $('image_download_link').observe('click', function() {SlideController.pause();});
    },

    showPhoto: function() {
        new Effect.Appear(this.photo, { duration: 1.0, queue: 'end', afterFinish: (function() { $(this.captionBox).show(); this.updateLinks();}).bind(this) });

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
