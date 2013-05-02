/**
 * jQuery Mobile taphold widget for listview rows that will display a popup.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 */

(function($, undefined) {

$.widget('mobile.listviewtaphold', $.mobile.widget, {

    options: {
        popupElt: $()
    },

    _create: function()
    {
        var opts = this.options,
            xy;

        this.element.on('taphold', 'li', function(e) {
            $li = $(e.currentTarget);

            $li.trigger('listviewtaphold');

            opts.popupElt.popup('open', {
                x: xy[0],
                y: xy[1]
            });

            return false;
        });

        this.element.on('vmousedown', 'li', function(e) {
            xy = [ e.pageX, e.pageY ];
        });

        this.element.on('contextmenu', function() {
            return false;
        });
    }

});

})(jQuery);
