/**
 * growler-jquery.js - Display 'growl'-like notifications for jQuery Mobile.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

(function($) {

    var closeNotify = function(elt)
    {
        $(elt).clearQueue().fadeOut(function() {
            var $this = $(this),
                p = $this.parent();
            $this.remove();
            if (!p.children().size()) {
                p.hide();
            }
        });
    },
    methods = {

        // opts: (object) Options [NONE currently]
        init: function(opts)
        {
            return this.each(function() {
                $(document).on('vclick.growler', '#' + $(this).attr('id') + ' DIV', function(e) {
                    e.stopPropagation();
                    closeNotify(e.target);
                });
            });
        },

        destroy: function()
        {
            $(document).off('.growler');
        },

        // opts: (object) Options:
        //   - delay: (integer) Fadeout delay.
        //   - raw: (boolean) Is msg raw text?
        //   - sticky: (boolean) Make notification sticky?
        notify: function(msg, type, opts)
        {
            return this.each(function() {
                var div,
                    $this = $(this);
                opts = $.extend({
                    delay: 5000,
                    raw: false,
                    sticky: false
                }, opts);

                div = $('<div class="' + type.replace('.', '-') + '">').hide();
                if (opts.raw) {
                    // TODO: This needs some fixing:
                    div.html(msg.replace('<a href=', '<a rel="external" href='));
                } else {
                    div.text(msg);
                }

                $this.show().append(div);
                div.fadeIn();

                if (!opts.sticky) {
                    div.delay(7000).queue(function() {
                        closeNotify(div);
                    });
                }
            });
        },

    };

    $.fn.growler = function(method)
    {
        if (methods[method]) {
            return methods[ method ].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        }
        $.error('Unknown method')
    };

})(jQuery);
