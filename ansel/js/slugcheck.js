/**
 */

var AnselSlugCheck = {

    // Set by calling code: text

    checkSlug: function()
    {
        var slug = $F('gallery_slug');

        // Empty slugs are always allowed.
        if (slug.length && slug != this.text) {
            HordeCore.doAction('checkSlug', {
                slug: slug
            }, {
                callback: this.checkSlugCallback.bind(this)
            });
        } else {
            this.checkSlugCallback(true);
        }
    },

    checkSlugCallback: function(r)
    {
        var slugFlag = $('slug_flag');

        if (r) {
            slugFlag.removeClassName('problem').addClassName('success');
            $('gallery_submit').enable();
            // In case we try various slugs
            this.text = slug;
        } else {
            slugFlag.removeClassName('success').addClassName('problem');
            $('gallery_submit').disable();
        }
    },

    onDomLoad: function()
    {
        $('gallery_slug').observe('change', this.checkSlug.bind(this));
    }

};

document.observe('dom:loaded', AnselSlugCheck.onDomLoad.bind(AnselSlugCheck));
