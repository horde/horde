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
                    $('tags').update(r.response);
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
        return !this.add();
    }

};
