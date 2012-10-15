/**
 * Autocomplete widget for jQuery Mobile.
 *
 * Copyright (c) 2012 The Horde Project (http://www.horde.org/)
 *
 * - Original released under MIT license -
 *
 * Name: autoComplete
 * Author: Raymond Camden & Andy Matthews
 * Contributors: Jim Pease (@jmpease)
 * Website: http://raymondcamden.com/, http://andyMatthews.net,
 *          https://github.com/commadelimited/autoComplete.js
 * Version: 1.4.3
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

$.widget('mobile.autocomplete', $.mobile.widget, {

    options: {
        callback: null,
        icon: 'arrow-r',
        link: null,
        matchFromStart: true,
        minLength: 0,
        source: null,
        target: $(),
        transition: 'fade'
    },

    _create: function()
    {
        var buildItems,
            el = this.element,
            self = this,
            opts = this.options,
            $target = $(opts.target);

        $target.on('click.autocomplete', 'li', function(e) {
            if ($(e.target).closest('a').length &&
                opts.callback !== null &&
                $.isFunction(opts.callback)) {
                opts.callback(e);
            }
            self.clear();
            return false;
        });

        buildItems = function(data)
        {
            $target.children().remove();

            if (data) {
                $.each(data, function(i, v) {
                    var li = $('<li>').jqmData('icon', opts.icon),
                        a = $('<a>').jqmData('transition', opts.transition).appendTo(li);

                    // are we working with objects or strings?
                    if ($.isPlainObject(v)) {
                        a.attr({ href: opts.link + encodeURIComponent(v.value) })
                            .jqmData('autocomplete', JSON.stringify(v))
                            .html(v.label);
                    } else {
                        a.attr({ href: opts.link + encodeURIComponent(v) })
                            .html(v);
                    }

                    $target.append(li);
                });
            }

            $target.listview("refresh");
        };

        el.off(".autocomplete");
        el.on("keyup.autocomplete", function() {
            var id = el.attr("id"),
                text = el.val();

            // If we don't have enough text zero out the target
            if (text.length < opts.minLength) {
                self.clear();
            } else {
                // Are we looking at a source array or remote data?
                if ($.isArray(opts.source)) {
                    buildItems(opts.source.sort().filter(function(elt) {
                        // matching from start, or anywhere in the string?
                        var re = opts.matchFromStart
                            ? new RegExp('^' + text, 'i')
                            : new RegExp(text, 'i');

                        return re.test($.isPlainObject(elt) ? elt.label : elt);
                    }));
                }
                else if ($.isFunction(opts.source)) {
                    // Accept a function as source.
                    // Function needs to call the callback, which is the first
                    // parameter.
                    // source: function(text, callback) {
                    //     mydata = [1,2];
                    //     callback(mydata);
                    // }
                    opts.source(text, buildItems);
                } else {
                    HordeMobile.doAction(
                        opts.source,
                        { search: text },
                        buildItems
                    );
                }
            }
        });
    },

    // Allow dynamic update of source and link
    update: function(options)
    {
        $.extend(this.options, options);
    },

    // Method to forcibly clear our target
    clear: function()
    {
        $(this.options.target)
            .html('')
            .listview('refresh')
            .closest("fieldset")
            .removeClass("ui-search-active");
    },

    // Method to destroy (cleanup) plugin
    destroy: function()
    {
        this.clear();
        $([ this.element, this.options.target ]).off('.autocomplete');
    }

});

})(jQuery);
