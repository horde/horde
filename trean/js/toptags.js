var TreanTopTags = {

    loadTags: function(r)
    {
        $('loadTags').hide();
        var t = new Element('ul', { className: 'horde-tags' });
        r.tags.each(function(tag) {
            if (tag == null) {
                return;
            }
            var item = new Element('li', { className: 'treanBookmarkTag' }).update(tag.escapeHTML());
            item.observe('click', function() { TreanTopTags.add(tag); });
            t.insert(item);
        });
        $('treanBookmarkTopTags').update(t);
        new Effect.Appear($('treanTopTagsWrapper'));
    },

    add: function(tag)
    {
        HordeImple.AutoCompleter.treanBookmarkTags.addNewItemNode(tag);
    }

}