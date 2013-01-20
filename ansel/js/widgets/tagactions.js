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
                AnselTagActions.updateTags(r);
            }
        });

        return false;
    },

    // Since onsubmit is never called when submitting programatically we
    // can use this function to add tags when we press enter on the tag form.
    submitcheck: function()
    {
        return !this.add();
    },

    updateTags: function(r)
    {
        $('tags').down('ul').remove();
        var ul = new Element('ul', { 'class': 'horde-tags' });
        $H(r).each(function(x) {
            var a = new Element('a', { 'href': x[1].link }).update(x[1].tag_name + '&nbsp;');
            var l = new Element('li').update(a);
            var r = new Element('img', {'src': this.remove_image });
            r.observe('click', function() { return this.remove(x[1].tag_id) }.bind(this));
            l.insert(r);
            ul.insert(l);
        }.bind(this));
        $('tags').insert({ 'top': ul });
    }

};
