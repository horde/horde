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
    main: null,
    bar: null,

    initialize: function(opts)
    {
        this.opts = Object.extend({
            mainClass: 'ansel-imageview-main',
            barClass: 'ansel-imageview-bar',
            closeClass: 'ansel-imageview-close'
        }, opts);

        this.buildDomSturcture();
    },

    buildDomSturcture: function()
    {
        var  bar, close;

        this.main = new Element('div', { class: this.opts.mainClass });
        this.bar = new Element('div', { class: this.opts.barClass });
        close = new Element('a' , { class: this.opts.closeClass }).update('&nbsp;');
        close.observe('click', function(e) { $(this.opts.container).fire('AnselImageView:close'); }.bind(this));
        this.bar.insert(close);
        this.bar.insert(new Element('div', { id: 'AnselViewImageMeta' }));
        $(this.opts.container).insert(this.main).insert(this.bar);
    },

    reset: function()
    {
        $(this.opts.container).update();
        this.buildDomSturcture();
    },

    showImage: function(im)
    {
        // Initial size?
        var img = new Element('img', {
            src: im.screen
        });
        this.main.update(img);
        this.bar.down('div').update(this.buildImageMetadata(im));
    },

    buildImageMetadata: function(im)
    {
        var meta = new Element('div', { class: 'ansel-image-title' }).update(im.t);
        var desc = new Element('div', { class: 'ansel-image-desc' }).update(im.c);
        var dt = new Element('div', {class: 'ansel-image-date' }).update(Ansel.text.taken + ': ' + Date.parse(im.d).toString('D'));
        var t = new Element('ul', {class: 'horde-tags' });//.update(im.tags.join(','));
        im.tags.each(function(tag) {
            t.insert(new Element('li').update(tag));
        });
        return new Element('div').insert(meta).insert(desc).insert(dt).insert(t);

    }

});