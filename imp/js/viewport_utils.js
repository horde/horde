/**
 * viewport_utils.js - Utility methods used by viewport.js.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

Object.extend(Array.prototype, {

    // Need our own diff() function because prototypejs's without() function
    // does not handle array input.
    diff: function(values)
    {
        return this.select(function(value) {
            return !values.include(value);
        });
    },

    numericSort: function()
    {
        return this.collect(Number).sort(function(a, b) {
            return (a > b) ? 1 : ((a < b) ? -1 : 0);
        });
    },

    // opts = (object) Additional options:
    //   - raw: (boolean) Force into parsing in raw mode (no sorting).
    toViewportUidString: function(opts)
    {
        opts = opts || {};

        var u = (opts.raw ? this.clone() : this.numericSort()),
            first = u.shift(),
            last = first,
            out = [];

        u.each(function(k) {
            if (!opts.raw && (last + 1 == k)) {
                last = k;
            } else {
                out.push(first + (last == first ? '' : (':' + last)));
                first = last = k;
            }
        });
        out.push(first + (last == first ? '' : (':' + last)));

        return out.join(',');
    }

});

Object.extend(String.prototype, {

    parseViewportUidString: function()
    {
        var out = [];

        this.strip().split(',').each(function(e) {
            var r = e.split(':');
            if (r.size() == 1) {
                out.push(Number(e));
            } else {
                out = out.concat($A($R(Number(r[0]), Number(r[1]))));
            }
        });

        return out;
    }

});
