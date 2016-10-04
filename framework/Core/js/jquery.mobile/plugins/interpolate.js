// https://github.com/jkarsrud/jquery-string-interpolator
;(function($) {
    "use strict";

    $.extend($, {
        interpolate: function(t, o, s) {
            var m = (!s) ? /#{([^{}]*)}/g : s;
            if (s) m = s;
            return t.replace(m, function(a, b) {
                var r = o[b];
                return typeof r === 'string' || typeof r === 'number' ? r : a;
            });
        }
    });
})(jQuery);