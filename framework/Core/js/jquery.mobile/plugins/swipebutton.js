/**
 * Swipebutton widget for jQuery Mobile.
 *
 * Copyright (c) 2012 The Horde Project (http://www.horde.org/)
 *
 * - Original released under MIT license -
 *
 * jquery.swipeButton.js - v1.2.0 - 2012-05-31
 * http://andymatthews.net/code/swipebutton/
 *
 * Copyright (c) 2012 andy matthews
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

(function($, undefined) {

$.widget('mobile.swipebutton', $.mobile.widget, {

    options: {
        action: 'swiperight'
    },

    _create: function()
    {
        var buttonDiv = 'divSwipe',
            removeButtonDiv = function(d) {
                d.hide(200, function() { d.remove(); });
            };

        this.element.on('click', 'li', function(e) {
            var $li = $(e.currentTarget),
                div = $li.children('.' + buttonDiv),
                a = $(e.target).closest('a');

            removeButtonDiv(div);

            if (a.length) {
                $.mobile.changePage(a.attr('href'), { data: $li });
                return false;
            }
        });

        this.element.on(this.options.action, 'li', function(e) {
            var div, h,
                ob = { buttons: [] },
                $li = $(e.currentTarget),
                existing = $(e.delegateTarget).find('.' + buttonDiv),
                add = !(existing.parent().is($li).length);

            // Remove currently displayed buttons
            removeButtonDiv(existing);

            // Add buttons, if needed
            if (add) {
                $li.trigger('swipebutton', ob);

                if (ob.buttons.length) {
                    h = $li.height();

                    div = $('<div></div>')
                        .addClass(buttonDiv)
                        .hide()
                        .height(h)
                        .css({
                            background: '#ddd',
                            position: 'absolute',
                            textAlign: 'center',
                            width: '100%',
                            zIndex: 5
                        });

                    $.each(ob.buttons, function(k, v) {
                        div.append(v);
                    });

                    $li.prepend(div);
                    div.show(200);
                }
            }

            return false;
        });
    }

});

})(jQuery);
