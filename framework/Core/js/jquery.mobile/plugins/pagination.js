/*
jQuery Mobile Framework: Pagination plugin.

Altered by the Horde Project (c) 2012 to work within the smartmobile
framework.

- Original released under MIT license -

Copyright (c) Filament Group, Inc
Authored by Scott Jehl, scott@filamentgroup.com

https://github.com/filamentgroup/jqm-pagination

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/
(function($, undefined) {

    $(document).bind("pagecreate", function(e) {
        $(":jqmData(role='pagination')", e.target).pagination();
    });

    $.widget("mobile.pagination", $.mobile.widget, {

        _create: function() {
            var $el = this.element,
                $page = $el.closest(".ui-page"),
                classNS = "ui-pagination",
                triggerClick = function(type) {
                    var elt = $el.find(".ui-pagination-" + type);
                    if (elt.filter(":visible").length) {
                        $page.trigger("click.pagination", type);
                        elt.find("a").blur();
                    }
                };

            // Add formatting to links
            $el.addClass(classNS).find("a").each(function() {
                $(this).buttonMarkup({
                    icon: "arrow-" + (($(this).closest("." + classNS + "-prev").length) ? "l" : "r"),
                    iconpos: "notext",
                    role: "button",
                    theme: "d"
                });
            });

            // Click handling
            $el.on("vclick", "a", function(e) {
                triggerClick($(e.target).closest("." + classNS + "-prev").length ? 'prev' : 'next');
                return false;
            });

            // Keyboard handling
            $(document).off("keyup.pagination").on("keyup.pagination", function(e) {
                if (!$(e.target).is("input, textarea, select, button")) {
                    switch (e.keyCode) {
                    case $.mobile.keyCode.LEFT:
                        triggerClick("prev");
                        e.preventDefault();
                        break;

                    case $.mobile.keyCode.RIGHT:
                        triggerClick("next");
                        e.preventDefault();
                        break;
                    }
                }
            });

            // Swipe handling
            $page.off("swipeleft.pagination").on("swipeleft.pagination", function(e) {
                triggerClick("prev");
            });
            $page.off("swiperight.pagination").on("swiperight.pagination", function(e) {
                triggerClick("next");
            });
        },

        enable: function(type, enable)
        {
            switch (type) {
            case 'next':
                $.fn[enable ? 'show' : 'hide'].call(this.element.find(".ui-pagination-next"));
                break;

            case 'prev':
                $.fn[enable ? 'show' : 'hide'].call(this.element.find(".ui-pagination-prev"));
                break;
            }
        }

    });

}(jQuery));
