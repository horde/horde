/**
 * jQuery Mobile taphold widget for listview rows that will display a popup.
 *
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 */

(function($, undefined) {

$.widget('mobile.listviewtaphold', $.mobile.widget, {

    options: {
        popupElt: $()
    },

    _create: function()
    {
        var $elt = this.element,
            opts = this.options,
            $li, xy;

        $elt.on('taphold', 'li', function(e) {
            $li = $(e.currentTarget);

            $li.trigger('listviewtaphold');

            opts.popupElt.popup('open', {
                x: xy[0],
                y: xy[1]
            });

            return false;
        });

        $elt.on('vmousedown', 'li', function(e) {
            $li = null;
            xy = [ e.pageX, e.pageY ];
        });

        $(document).on('contextmenu', function(e) {
            if ($li) {
                $li = null;
                return false;
            }
            return !$elt.find(e.target).size();
        });
    }

});

})(jQuery);
