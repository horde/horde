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

    currentGallery: null,

    /**
     * Currently loaded image thumbnails
     */
    //imgs: [],

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
     * Callback for after a gallery is loaded.
     *
     * @param object r  The response object
     */
    galleryLoaded: function(r)
    {
        // TODO: error checks, build any subgallery lists etc...
        if ($.mobile.currentPage != 'galleryview' &&
            AnselMobile.currentGallery && (r.id == AnselMobile.currentGallery)) {

            $.mobile.changePage('galleryview', 'slide', false, true);
            return;
        }

        //AnselMobile.imgs = r.imgs;
        AnselMobile.currentGallery = r.id;
        if (r.sg.length) {
            var l = $('<ul>').addClass('anselgalleries').attr({ 'data-role': 'listview', 'data-inset': 'true' });
            $('#thumbs').before(AnselMobile.buildGalleryList(l, r.sg).listview());
        } else {
            $('.anselgalleries').detach();
        }
        $('#galleryview h1').text(r.n);
        if ($.mobile.activePage.attr('id') != 'galleryview') {
            $.mobile.changePage('galleryview', 'slide', false, true);
        }
        $('#thumbs').empty();
        $.each(r.imgs, function(k, i) {
            var img = $('<li>').addClass('anselthumb').append($('<a>').attr({ 'href': '#' }).append($('<img>').attr({ src: i.url })));
            $('#thumbs').append(img);
        });
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