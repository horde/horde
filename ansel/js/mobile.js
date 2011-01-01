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
     * Build a gallery list
     */
    buildGalleryList: function(galleries)
    {
        var list = $('<ul>')
            .addClass('anselGalleryList')
            .attr({ 'data-role': 'listview' }), item;
        $('#anselgallerylist ul').detach();
        $.each(galleries, function(k, g) {
            var item = $('<li>').attr({ 'class': 'ansel-gallery', 'ansel-gallery-id': g.id });
            item.append($('<img>').attr({ src: g.ki }));
            item.append($('<h3>').append($('<a>').attr({ href: '#' }).text(g.n)));
            item.append($('<p>').text(g.d));
            list.append(item);
        });

        $('#anselgallerylist').append(list);
    },

    /**
     * Load the specified gallery
     */
    toGallery: function(id)
    {
        HordeMobile.doAction('getGallery', { id: id }, AnselMobile.galleryLoaded);
    },

    /**
     * Callback for after a gallery is loaded.
     */
    galleryLoaded: function(r)
    {
        $('#galleryview h1').text(r.n);
        // TODO: error checks, build any subgallery lists etc...
        $.mobile.changePage('galleryview', 'slide', false, true);
        $.each(r.imgs, function(k, i) {
            var img = $('<li>').append($('<img>').attr({ src: i.url }));
            $('#thumbs').append(img);
        });
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
                alert(elt.attr('ansel-gallery-id'));
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

        // Todo, eventually move to mobile callback so page reloads work etc...
        AnselMobile.buildGalleryList(Ansel.conf.galleries);
    }
};
$(AnselMobile.onDocumentReady);