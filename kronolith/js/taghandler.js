/**
 * Tag action handler for non-ajax views.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Core
 */

var KronolithTagHandler = Class.create({

    /**
     * Initialize and bind events.
     *
     * @param string element  The id of the resource for this tagger.
     * @param array params    Additional parameters.
     */
    initialize: function(id, params)
    {
        this.id = id;
        this.inputbox = 'newtags-input_' + id;
        this.button = 'newtags-button_' + id;
        this.div = 'tags_' + id;

        $(this.button).observe('click', function(e) {
            this.tag();
            e.stop();
        }.bind(this));

        new Ajax.Request(
            KronolithVar.URI_AJAX + 'tagAction',
            {
                method: 'post',
                parameters: { token: KronolithVar.TOKEN, resource: this.id, type: 'calendar', action: 'list' },
                onSuccess: this._tagListCallback.bind(this)
            }
        );
    },

    _tagListCallback: function(r)
    {
        $(this.div).update();
        var tags = $H(r.responseJSON.response.tags);
        tags.each(function(t) {
            var d = new Element('a', { 'href': '#' }).update(
                new Element('img', { 'src': KronolithVar.deletetag_img })).observe(
                'click',
                function(e) {
                    this.untag(e.element(), t.key);
                    e.stop();
                }.bind(this)
            );
            var l = new Element('li', { 'class': 'panel-tags' }).update(t.value).insert(d);
            $(this.div).insert(l);
        }.bind(this));
    },

    tag: function()
    {
        if (!($(this.inputbox).value.blank())) {
            new Ajax.Request(
                KronolithVar.URI_AJAX + 'tagAction',
                {
                    method: 'post',
                    parameters: { token: KronolithVar.TOKEN, resource: this.id, type: 'calendar', action: 'add', tags: encodeURIComponent($(this.inputbox).value) },
                    onSuccess: this._tagListCallback.bind(this)
                }
            );
        }
    },

    untag: function(el, tagid)
    {
        new Ajax.Request(
            KronolithVar.URI_AJAX + 'tagAction',
            {
                method: 'post',
                parameters: { resource: this.id, type: 'calendar', tags: tagid, action: 'remove', token: KronolithVar.TOKEN },
                onSuccess: this._untagCallback.curry(el).bind(this)
            }
        );
    },

    _untagCallback: function(el, r)
    {
        el.up('li').remove();
    }

});
