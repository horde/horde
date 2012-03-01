/**
 * compose-base.js - Provides basic compose javascript functions shared
 * between standarad and dynamic displays.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ImpComposeBase = {

    // Vars defaulting to null: editor_on, identities

    setCursorPosition: function(input, type)
    {
        var pos, range;

        if (!(input = $(input))) {
            return;
        }

        switch (type) {
        case 'top':
            pos = 0;
            input.setValue('\n' + $F(input));
            break;

        case 'bottom':
            pos = $F(input).length;
            break;

        default:
            return;
        }

        if (input.setSelectionRange) {
            /* This works in Mozilla. */
            Field.focus(input);
            input.setSelectionRange(pos, pos);
            if (pos) {
                (function() { input.scrollTop = input.scrollHeight - input.offsetHeight; }).defer();
            }
        } else if (input.createTextRange) {
            /* This works in IE */
            range = input.createTextRange();
            range.collapse(true);
            range.moveStart('character', pos);
            range.moveEnd('character', 0);
            Field.select(range);
            range.scrollIntoView(true);
        }
    },

    updateAddressField: function(elt, address)
    {
        var v;

        if (elt.value.length) {
            v = elt.value.replace(/, +/g, ',').split(',').findAll(function(s) { return s; });
            elt.value = v.join(', ');
            if (elt.value.lastIndexOf(';') != elt.value.length - 1) {
                elt.value += ',';
            }
            elt.value += ' ' + address;
        } else {
            elt.value = address;
        }

        if (address.lastIndexOf(';') != address.length - 1) {
            elt.value += ', ';
        }
    }

};
