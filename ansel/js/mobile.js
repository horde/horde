/**
 * mobile.js - Base mobile application logic.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
var AnselMobile = {

    /**
     * The currently displayed gallery
     *
     * @var object
     */
    currentGallery: null,

    /**
     * Array of images in the currentGallery
     *
     * @var array
     */
    currentImages: null,

    /**
     * The index in currentImages[] for the currently displayed image
     *
     * @var integer
     */
    currentImage: null,

    /**
     * Build a gallery list
     *
     * @param object l   The ul object to append to
     * @param object gs  A hash of the galleries
     *
     * @return a ul dom object
     */
    buildGalleryList: function(l, gs)
    {
        $.each(gs, function(k, g) {
            var item = $('<li>').attr({ 'ansel-gallery-id': g.id }).addClass('ansel-gallery');
            item.append($('<img>').attr({ src: g.ki }).addClass('ui-li-icon'));
            item.append($('<h3>').append($('<a>').attr({ href: '#' }).text(g.n)));
            item.append($('<p>').text(g.d));
            l.append(item);
        });

        return l;
    },

    /**
     * Build a <UL> node to hold the current gallery's subgalleries
     *
     * @return dom object
     */
    getSubGalleryUL: function()
    {
        return $('<ul>').addClass('anselgalleries').attr({ 'data-role': 'listview', 'data-inset': 'true' });
    },

    /**
     * Load the specified gallery
     *
     * @param integer id  The gallery id to return
     */
    toGallery: function(id)
    {
        HordeMobile.doAction('getGallery', { id: id }, AnselMobile.galleryLoaded);
    },

    /**
     * Display the selected image
     *
     * @param integer index  The index into the currentImages array
     */
    toImage: function(index)
    {
        var i = $('<img>').load(
            function() {
                AnselMobile.resize($(this));
            }).attr({ 'src': ((AnselMobile.currentGallery.tiny) ? 'http://i.tinysrc.mobi/' : '') + AnselMobile.currentImages[index].screen });

        AnselMobile.currentImage = index;
        $('#anselimageview').empty();
        $('#anselimageview').append(i);
        $('#imageview h1').text(AnselMobile.currentImages[index].fn)
        $('#ansel-image-back .ui-btn-text').text(AnselMobile.currentGallery.n);

        if ($.mobile.activePage.attr('id') != 'imageview') {
            $.mobile.changePage('imageview', 'slide', false, true);
        }
    },

    /**
     * Callback for after a gallery is loaded.
     *
     * @param object r  The response object
     */
    galleryLoaded: function(r)
    {
        // TODO: error checks, build any subgallery lists etc...
        if ($.mobile.currentPage != 'galleryview' &&
            AnselMobile.currentGallery && (r.id == AnselMobile.currentGallery.id)) {

            $.mobile.changePage('galleryview', 'slide', false, true);
            return;
        }

        AnselMobile.currentGallery = r;
        $('.anselgalleries').detach();
        if (r.sg.length) {
            var l = $('<ul>').addClass('anselgalleries').attr({ 'data-role': 'listview', 'data-inset': 'true' });
            $('#thumbs').before(AnselMobile.buildGalleryList(l, r.sg).listview());
        }
        $('#galleryview h1').text(r.n);
        $('#thumbs').empty();
        AnselMobile.currentImages = r.imgs;
        $.each(r.imgs, function(k, i) {
            var img = $('<li>').addClass('anselthumb').append($('<a>').attr({ 'href': '#', 'image-key': k, }).append($('<img>').attr({ 'width': Ansel.conf.thumbWidth, 'height': Ansel.conf.thumbHeight, src: i.url })));
            $('#thumbs').append(img);
        });
        if ($.mobile.activePage.attr('id') != 'galleryview') {
            $.mobile.changePage('galleryview', 'slide', false, true);
        }
        if (r.p) {
            $('#ansel-gallery-back .ui-btn-text').text(r.pn);
            $('#ansel-gallery-back').attr({ 'action': 'gallery', 'gallery-id': r.p });
        } else {
            $('#ansel-gallery-back .ui-btn-text').text($.mobile.page.prototype.options.backBtnText);
            $('#ansel-gallery-back').attr({ 'action': 'home', 'gallery-id': null });
        }
        AnselMobile.centerGrid();
    },

    /**
     * Utility function to attempt to center the thumbnail grid
     *
     * Logic unabashedly borrowed from:
     *  http://tympanus.net/codrops/2010/05/27/awesome-mobile-image-gallery-web-app/
     */
    centerGrid: function()
    {
		if ($('.anselthumb').size() > 0) {
			var perRow = Math.floor($(window).width() / 80);
			var left = Math.floor(($(window).width() - (perRow * 80)) / 2);
			$('.anselthumb').each(function(i) {
				var $this = $(this);
				if (i % perRow == 0) {
					$this.css('margin-left', left + 'px');
				} else {
					$this.css('margin-left', '0px');
				}
			});
		}
    },

	/**
     * Resize the image, based on windows width and height.
     *
     * @param dom object $image the image node
     */
	resize: function($image)
    {
		var widthMargin = 10;
		var heightMargin = 80;

		var windowH = $(window).height() - heightMargin;
		var windowW = $(window).width() - widthMargin;
		var theImage = new Image();
		theImage.src = $image.attr('src');
		var imgwidth = theImage.width;
		var imgheight = theImage.height;
		if (imgwidth > windowW || imgheight > windowH) {
			if (imgwidth > imgheight) {
				var newwidth = windowW;
				var ratio = imgwidth / windowW;
				var newheight = imgheight / ratio;
				theImage.height = newheight;
				theImage.width= newwidth;
				if (newheight > windowH) {
					var newnewheight = windowH;
					var newratio = newheight / windowH;
					var newnewwidth =newwidth / newratio;
					theImage.width = newnewwidth;
					theImage.height= newnewheight;
				}
			} else {
				var newheight = windowH;
				var ratio = imgheight / windowH;
				var newwidth = imgwidth / ratio;
				theImage.height = newheight;
				theImage.width = newwidth;
				if (newwidth > windowW) {
					var newnewwidth = windowW;
					var newratio = newwidth / windowW;
					var newnewheight =newheight / newratio;
					theImage.height = newnewheight;
					theImage.width = newnewwidth;
				}
			}
		}
		$image.css({ 'width': theImage.width + 'px', 'height': theImage.height + 'px' });
    },

    /**
     * Global click handler
     *
     */
    clickHandler: function(e)
    {
        var elt = $(e.target), id;

        while (elt && elt != window.document && elt.parent().length) {
            // Navigate to a gallery
            if (elt.hasClass('ansel-gallery')) {
                AnselMobile.toGallery(elt.attr('ansel-gallery-id'));
                return;
            } else if (elt.attr('image-key')) {
                AnselMobile.toImage(elt.attr('image-key'));
                return;
            }

            switch (elt.attr('id')) {
                case 'ansel-image-prev':
                    AnselMobile.prevImage();
                    return;
                case 'ansel-image-next':
                    AnselMobile.nextImage();
                    return;
                case 'ansel-gallery-back':
                    switch (elt.attr('action')) {
                    case 'home':
                        $.mobile.changePage('gallerylist', 'slide', true, true);
                        break;
                    case 'gallery':
                        AnselMobile.toGallery(elt.attr('gallery-id'));
                    }
                    return;
                case 'ansel-image-back':
                    window.history.back();
                    return;
            }
            elt = elt.parent();
        }
    },

    /**
     * Global swipe handler
     *
     */
    handleSwipe: function(map)
    {
        if ($.mobile.activePage.attr('id') != 'imageview') {
            return;
        }
        if (map.type == 'swipeleft') {
            AnselMobile.nextImage();
        } else if (map.type == 'swiperight') {
            AnselMobile.prevImage();
        }
    },

    nextImage: function()
    {
        AnselMobile.currentImage++;
        if (AnselMobile.currentImage >= AnselMobile.currentImages.length) {
            AnselMobile.currentImage = 0;
        }
        AnselMobile.toImage(AnselMobile.currentImage);
    },

    prevImage: function()
    {
        AnselMobile.currentImage--;
        if (AnselMobile.currentImage < 0) {
            AnselMobile.currentImage = AnselMobile.currentImages.length - 1;
        }
        AnselMobile.toImage(AnselMobile.currentImage);
    },

    /**
     * Initial document ready entry point
     *
     */
    onDocumentReady: function()
    {
        // Set up HordeMobile.
        HordeMobile.urls.ajax = Ansel.conf.URI_AJAX;

        // Bind click and swipe events
        $(document).click(AnselMobile.clickHandler);
        $('body').bind('swipeleft', AnselMobile.handleSwipe);
        $('body').bind('swiperight', AnselMobile.handleSwipe);

        var list = $('<ul>').addClass('anselgallerylist').attr({ 'data-role': 'listview' });
        $('#anselgallerylist').append(AnselMobile.buildGalleryList(list, Ansel.conf.galleries, 'anselgallerylist'));

        // We need to recenter the thumbnail grid, and (eventually) try to
        // resize the main image if it's  being shown.
        $(window).bind('resize', function() {
            AnselMobile.centerGrid();
        });
    }
};
$(AnselMobile.onDocumentReady);
