/**
 */

var AnselTagActions = {

    // Set by calling script: gallery, image

    add: function()
    {
        if (!$('addtag').value.blank()) {
            HordeCore.doAction('addTag', {
                gallery: this.gallery,
                image: this.image,
                tags: $F('addtag')
            }, {
                callback: function(r) {
                    $('addtag').value = "";
                    AnselTagActions.updateTags(r);
                }
            });
        }

        return true;
    },

    remove: function(tagid)
    {
        HordeCore.doAction('removeTag', {
            gallery: this.gallery,
            image: this.image,
            tags: tagid
        }, {
            callback: function(r) {
                $('tags').update(r.response);
            }
        });

        return true;
    },

    // Since onsubmit is never called when submitting programatically we
    // can use this function to add tags when we press enter on the tag form.
    submitcheck: function()
    {
        return !this.add();
    },

    updateTags: function(r)
    {
        var ul = new Element('ul', { 'class': 'horde-tags' });
        console.log(r);
        $H(r).each(function(x) {
            var a = new Element('a', { 'href': x[1].link }).update(x[1].tag_name);
            var l = new Element('li').update(a);
            ul.update(l);
        });
        $('tags').insert({ 'top': ul });
    }

};
