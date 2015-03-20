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
        this.main = $('imageviewmain');
        this.imagediv = $('imageviewimage');
        this.bar = $('bottombar');
        this.topbar = $('topbar');
        this.sub = $('AnselImageViewSub');
        // var  bar, close, main;

        // this.topbar = new Element('div', { class: 'ansel-imageview-topbar' });

        // // @todo - these are temp placeholders
        // this.topbar.insert(new Element('div', { }).insert('[breadcrumbs] [left] [right]'));

        // this.imagediv = new Element('div', { class: this.opts.mainClass });
        // this.bar = new Element('div', { class: this.opts.barClass });
        var close = new Element('a' , { class: this.opts.closeClass }).update('[close]');
        close.observe('click', function(e) { $(this.opts.container).fire('AnselImageView:close'); }.bind(this));
        this.topbar.insert(close);
        // this.bar.insert(new Element('div', { id: 'AnselViewImageMeta' }));

        // this.main = new Element('div', { class: 'ansel-imageview-main' });
        // this.sub = new Element('div', { class: 'ansel-imageview-sub' });
        // this.main.insert(this.topbar).insert(this.imagediv).insert(this.bar);
        // $(this.opts.container).insert(this.main).insert(this.sub.update('<br />foobar<div>foo bar</div>'));
    },

    reset: function()
    {
        //$(this.opts.container).update();
        this.buildDomSturcture();
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
//        this.bar.down('div').update(this.buildImageMetadata(im));
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

    onResize: function()
    {
        var h = window.innerHeight, w = window.innerWidth;
        this.imagediv.style.height = h - 81 + 'px';
        this.bar.style.top = h - 80 + 'px';
        this.bar.style.height = $('imageviewimage').getHeight() + 10 + 'px';
        this.sub.style.top = $('imageviewmain').getHeight() + 10 + 'px';
    }

});