/**
 * imageview.js - Wrap functionality for maintaining the ajax image view.
 *
 *
 * Usage: var view = new AnselImageView({ container: 'myContainer' });
 *
 * Required Options:
 *     - container:     The container element id.
 *
 * Optional:
 *
 *     - mainClass:  The main image container.
 *     - barClass:   The bar container.
 *
 *
 *
 */
AnselImageView = Class.create({

    opts: {},
    imagediv: null,
    main: null,
    bar: null,
    leftdiv: null,
    rightdiv: null,

    initialize: function(opts)
    {
        this.opts = Object.extend({
            mainClass: 'ansel-imageview-image',
            barClass: 'ansel-imageview-bar',
            closeClass: 'ansel-imageview-close'
        }, opts);

        this.buildDomSturcture();
        Element.observe(window, 'resize', this.onResize.bindAsEventListener(this));
    },

    buildDomSturcture: function()
    {
        var close = new Element('a' , { class: this.opts.closeClass }).update('[close]');

        this.main = $('imageviewmain');
        this.imagediv = $('imageviewimage');
        this.bar = $('bottombar');
        this.topbar = $('topbar');
        this.sub = $('AnselImageViewSub');
        close.observe('click', function(e) { $(this.opts.container).fire('AnselImageView:close'); }.bind(this));
        this.topbar.insert(close);
    },

    reset: function()
    {
        this.imagediv.update();
        $('AnselViewImageData').update();
    },

    showImage: function(im)
    {
        // Initial size?
        var img = new Element('img', {
            src: im.screen
        });
        this.imagediv.update(img);
        this.buildImageMetadata(im);
        this.onResize();
        this.enableKeyboardNav();
    },

    buildImageMetadata: function(im)
    {
        this.leftdiv = new Element('div', { class: 'ansel-image-leftmeta' });
        this.leftdiv.insert(new Element('div', { class: 'ansel-image-title' }).update(im.t)).insert(
            new Element('div', { class: 'ansel-image-desc' }).update(im.c));

        this.rightdiv = new Element('div', { class: 'ansel-image-rightmeta' });
        this.rightdiv.insert(
            new Element('div', { class: 'ansel-image-date' }).update(Ansel.text.taken + ' ' + Date.parse(im.d).toString('D'))).
            insert(
                new Element('div', { class: 'ansel-image-links' }).update('**todo**[share] [edit] [download]')
            );

        // @todo - tags.
        // var t = new Element('ul', { class: 'horde-tags' });//.update(im.tags.join(','));
        // im.tags.each(function(tag) {
        //     t.insert(new Element('li').update(tag));
        // });

        $('AnselViewImageData').update(this.leftdiv).insert(this.rightdiv);
    },

    enableKeyboardNav: function() {
        document.observe('keydown', this.keyboardAction.bind(this));
    },

    disableKeyboardNav: function() {
        document.stopObserving('keydown', this.keyboardAction.bind(this));
    },

    keyboardAction: function(e) {
        var keycode = e.keyCode, escapeKey, key;
        if (e.DOM_VK_ESCAPE) {  // mozilla
            escapeKey = e.DOM_VK_ESCAPE;
        } else { // ie
            escapeKey = 27;
        }

        key = String.fromCharCode(keycode).toLowerCase();
        console.log(key);
        if (key.match(/x|o|c/) || (keycode == escapeKey)){ // close lightbox
            $(this.opts.container).fire('AnselImageView:close');
        } else if ((key == 'p') || (keycode == 37)){ // display previous image
            $(this.opts.container).fire('AnselImageView:previous');
        } else if ((key == 'n') || (keycode == 39)){ // display next image
            $(this.opts.container).fire('AnselImageView:next');
        }
    },

    onResize: function()
    {
        var h = window.innerHeight, w = window.innerWidth;
        this.imagediv.style.height = h - 81 + 'px';
        this.bar.style.top = h - 80 + 'px';
        this.bar.style.height = $('imageviewimage').getHeight() + 10 + 'px';
        this.sub.style.top = $('imageviewmain').getHeight() + 10 + 'px';
    }

});