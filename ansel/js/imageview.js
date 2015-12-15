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
 * Custom Events:  Fired on the opts.container element passed into the
 *                 constructor.
 *
 *     - AnselImageView:close     Fired when the imageview has been requested to
 *                                be closed. It is the calling code's
 *                                responsibility to close the view.
 *
 *     - AnselImageView:previous  Fired when the previous image is requested.
 *
 *     - AnselImageView:next      Fired when the next image is requested.
 */
AnselImageView = Class.create({

    opts: {},
    imagediv: null,
    main: null,
    bar: null,
    leftdiv: null,
    rightdiv: null,
    currentImage: null,
    keyHandler: null,
    clickHandler: null,

    initialize: function(opts)
    {
        this.opts = Object.extend({
            mainClass: 'ansel-imageview-image',
            barClass: 'ansel-imageview-bar',
            nextClass: 'ansel-imageview-next',
            prevClass: 'ansel-imageview-prev',
            closeId: 'imageviewclose'
        }, opts);

        this.buildDomSturcture();
        Element.observe(window, 'resize', this.onResize.bindAsEventListener(this));
        this.keyHandler = this.keyboardAction.bind(this);
        this.clickHandler = this.clickAction.bind(this);
    },

    buildDomSturcture: function()
    {
        var close = $('imageviewclose');

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
        var img;

        if (!im) {
            this.enableKeyboardNav();
            return;
        }

        // Initial size?
        img = new Element('img', {
            src: im.screen
        });
        this.currentImage = im;
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
        document.observe('keydown', this.keyHandler);
        document.observe('click', this.clickHandler);
    },

    disableKeyboardNav: function() {
       document.stopObserving('keydown', this.keyHandler);
       document.stopObserving('click', this.clickHandler);
    },

    keyboardAction: function(e) {
        var keycode = e.keyCode, escapeKey, key;
        if (this.inKeyHandler) {
            return;
        }
        this.inKeyHandler = true;
        if (e.DOM_VK_ESCAPE) {  // mozilla
            escapeKey = e.DOM_VK_ESCAPE;
        } else { // ie
            escapeKey = 27;
        }

        key = String.fromCharCode(keycode).toLowerCase();
        if (key.match(/x|o|c/) || (keycode == escapeKey)) { // close lightbox
            this.disableKeyboardNav();
            $(this.opts.container).fire('AnselImageView:close');
        } else if ((key == 'p') || (keycode == 37)) { // display previous image
            this.disableKeyboardNav();
            $(this.opts.container).fire('AnselImageView:previous');
        } else if ((key == 'n') || (keycode == 39)) { // display next image
            this.disableKeyboardNav();
            $(this.opts.container).fire('AnselImageView:next');
        }
        this.inKeyHandler = false;
    },

    clickAction: function(e)
    {
        if (this.inClickHandler) {
            return;
        }
        if (e.isRightClick() || typeof e.element != 'function') {
            return;
        }
        this.inClickHandler = true;
        var elt = e.element(), id;
        while (Object.isElement(elt)) {
            if (elt.hasClassName(this.opts.nextClass)) {
                this.disableKeyboardNav();
                $(this.opts.container).fire('AnselImageView:next');
                e.stop();
                this.inClickHandler = false;
                return;
            } else if (elt.hasClassName(this.opts.prevClass)) {
                this.disableKeyboardNav();
                $(this.opts.container).fire('AnselImageView:previous');
                e.stop();
                this.inClickHandler = false;
                return;
            }
            elt = elt.up();
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