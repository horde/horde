/**
 * Horde Form Assign Field Javascript Class
 *
 * Provides the javascript class to accompany the Horde_Form assign field.
 *
 * Copyright 2004-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
*
* @author Jan Schneider <jan@horde.org>
 */

var Horde_Form_Assign = {

    deselectHeaders: function(form, elt, side)
    {
        document.forms[form].elements[elt + (side ? '__right' : '__left')][0].selected = false;
    },

    move: function(form, elt, direction)
    {
        var from, i, to,
            left = document.forms[form].elements[elt + '__left'],
            right = document.forms[form].elements[elt + '__right'];

        if (direction) {
            from = right;
            to = left;
        } else {
            from = left;
            to = right;
        }

        for (i = 0; i < from.length; ++i) {
            if (from[i].selected) {
                to[to.length] = new Option(from[i].text, from[i].value);
                to[to.length - 1].ondblclick = function() {
                    Horde_Form_Assign.move(form, elt, 1 - direction);
                };
                from[i--] = null;
            }
        }

        this.setField(form, elt);
    },

    setField: function(form, elt)
    {
        var i,
            hit = false,
            left = document.forms[form].elements[elt + '__left'],
            right = document.forms[form].elements[elt + '__right'],
            values = '';

        for (i = 0; i < left.options.length; ++i) {
            if (i === 0 && !left[i].value) {
                continue;
            }
            values += left.options[i].value + '\t';
            hit = true;
        }

        if (hit) {
            values = values.substring(0, values.length - 1);
            hit = false;
        }

        values += '\t\t';

        for (i = 0; i < right.options.length; ++i) {
            if (i === 0 && !right[i].value) {
                continue;
            }
            values += right.options[i].value + '\t';
            hit = true;
        }

        if (hit) {
            values = values.substring(0, values.length - 1);
        }
        document.forms[form].elements[elt + '__values'].value = values;
    }

};
